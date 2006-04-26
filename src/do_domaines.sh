#!/bin/ksh
#
# $Id: do_domaines.sh,v 1.32 2005/08/31 02:55:38 darcs Exp $
# ----------------------------------------------------------------------
# AlternC - Web Hosting System
# Copyright (C) 2002 by the AlternC Development Team.
# http://alternc.org/
# ----------------------------------------------------------------------
# Based on:
# Valentin Lacambre's web hosting softwares: http://altern.org/
# ----------------------------------------------------------------------
# LICENSE
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License (GPL)
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# To read the license please visit http://www.gnu.org/copyleft/gpl.html
# ----------------------------------------------------------------------
# Original Author of file: Jerome Moinet for l'Autre Net - 14/12/2000
# Purpose of file: system level domain management
# ----------------------------------------------------------------------
#

# ####################################################################
# VARIABLES SETTINGS : 
# ####################################################################

umask 022

integer nb1
integer nb2
integer ORDRE

[ -x "/usr/bin/get_account_by_domain" ] || { echo "You have to install alternc-admintools: apt-get update ; apt-get install alternc-admintools" ; exit 1 ; }

DOM_ROOT=/usr/lib/alternc/system
FIC_TMP=/tmp/domaines.tmp
FIC_TMP_SUB=/tmp/sub_domaines.tmp
FIC_LOCK=/var/run/alternc/cron.lock
FIC_LOG=/var/log/alternc/domaines.log
FIC_LOG_SUB=/var/log/alternc/sub_domaines.log
FIC_TMP_OVERRIDE_PHP=/tmp/override_php.tmp
HTTP_DNS=/var/alternc/dns
HTML_HOME=/var/alternc/html
NAMED_HOME=/etc/bind
NAMED_ETC=${NAMED_HOME}
NAMED_MASTER=${NAMED_HOME}/master
NAMED_TPL=domaines.template
SLAVE_TPL=slave.template
SECONDARY_LIST=secondary.list
NAMED_CONF=automatic.conf
RELOAD_NAMED=/etc/init.d/bind9
RELOAD_APACHE=/etc/init.d/apache
WEBMAIL_ROOT=/var/alternc/dns/redir/mail
DATA_ROOT=var/alternc

ACTION_INSERT=0
ACTION_UPDATE=1
ACTION_DELETE=2
TYPE_LOCAL=0
TYPE_URL=1
TYPE_IP=2
TYPE_WEBMAIL=3
SLAVE=2
OUI=1
NON=0

wc=/usr/bin/wc
awk=/usr/bin/awk
echo=/bin/echo
cut=/usr/bin/cut
grep=/bin/grep
egrep=/bin/egrep
tail=/usr/bin/tail
head=/usr/bin/head
rm=/bin/rm
find=/usr/bin/find
cat=/bin/cat
sed=/bin/sed
mv=/bin/mv
ln=/bin/ln
date=/bin/date
printf=/usr/bin/printf
cp=/bin/cp
env=/usr/bin/env
sort=/usr/bin/sort
mkdir=/bin/mkdir
mktemp=/bin/mktemp

# récupération des passwd et autres à partir d'un fichier externe :
FIC_CONF=/etc/alternc/local.sh
[ -r "$FIC_CONF" ] && . $FIC_CONF || { echo "Le fichier de configuration $FIC_CONF est absent ou est inaccessible" ; exit 1 ; }

# On teste si les variables attendues sont renseignées :
for variable in MYSQL_HOST MYSQL_DATABASE MYSQL_USER MYSQL_PASS DEFAULT_MX PUBLIC_IP ; do
	var=""
	var=`set | $grep $variable | $grep -v variable`
	var=`$echo $var | $cut -d= -f2`
	[ -z "$var" ] && { $echo "la variable \$$variable n'est pas renseignée." ; exit 1 ; }
done


WEBMAIL_ROOT=/$DATA_ROOT/bureau/admin/webmail/
DOM_ROOT=/$DATA_ROOT/exec/system
FIC_LOCK=/$DATA_ROOT/bureau/cron.lock
HTTP_DNS=/$DATA_ROOT/dns
HTML_HOME=/$DATA_ROOT/html

MYSQL_SELECT="/usr/bin/mysql -h${MYSQL_HOST} -u${MYSQL_USER} -p${MYSQL_PASS} -Bs ${MYSQL_DATABASE} -e "
MYSQL_DELETE="/usr/bin/mysql -h${MYSQL_HOST} -u${MYSQL_USER} -p${MYSQL_PASS} ${MYSQL_DATABASE} -e "


# ####################################################################
# FUNCTIONS :
# ####################################################################

# la première lettre de l'avant-dernière partie du domaine (e.g.
# www.alternc.org -> a)
#
# argument: le domaine
# imprime: la lettre
function init_dom_letter
{
    echo $1 | awk '{z=split($NF, a, ".") ; print substr(a[z-1], 1, 1)}'
}

function fix_master_perms
{
    chown :bind $1
    chmod 640 $1
}

#---------------
# A chaque modification du fichier named
# d'un domaine, on incrémente son serial.
# Le serial est de la forme YYYYYMMDDSS
# où SS est le numéro d'ordre dans la journée.
# On incrémente ce numéro d'ordre si la modif
# est du même jour que la précédente, sinon on
# met la date du jour avec 01 en numéro d'ordre.
# Prend le nom du fic de conf en argument.
#---------------
# we assume that the serial line contains the "serial string", eg.:
#                 2005012703      ; serial
#
# returns 1 if file isn't readable
# returns 2 if we can't find the serial number
# returns 3 if a tempfile can't be created
function increment_serial {
    if [ -f "$1" ]; then
        # the assumption is here
        SERIAL=`$awk '/^..*serial/ {print $1}' < $1` || return 2
        if [ ! -z "${SERIAL}" ]; then
            DATE=`$echo $SERIAL | $cut -c1-8`
            ORDRE=`$echo $SERIAL | $sed s/"${DATE}0\?"/""/g`
            DATE_JOUR=`$date +%Y%m%d`
            # increment the serial number only if the date hasn't changed
            if [ "X$DATE" = "X$DATE_JOUR" ] ; then
                ORDRE=$(($ORDRE+1))
            else
                ORDRE=1
                DATE=$DATE_JOUR
            fi
            NEW_SERIAL=$DATE`$printf "%.2d" $ORDRE`
            TMPFILE=`$mktemp $1.XXXXXX` || return 3
            # put the serial number in place
            $awk -v NEW_SERIAL=$NEW_SERIAL '{if ($3 =="serial") print "		"NEW_SERIAL "	; serial"; else print $0}' < $1 > $TMPFILE && \
                $mv -f $TMPFILE $1 && \
		fix_master_perms $1
            return 0
        else
            return 2
        fi
    else
        return 1
    fi
}

#---------------
# Modification de l'ip d'un sous_domaine.
# dans la conf named. La rajoute si manquante.
# Prend domaine, ip et
# sous_domaine en arguments.
#---------------
function modifier_ip_sous_domaine
{

        DOM=$1
        IP=$2
        SUB=$3
        PAT="^$SUB[[:space:]]*IN[[:space:]]*A[[:space:]]*.*\$"
        if [ "X" = "X$SUB" ]
        then
            SUB="@"
        fi
        DOMLINE="$SUB 	IN	A 	$IP"

        if [ -f "$NAMED_MASTER/$DOM" ] ; then
            if grep -q $PAT $NAMED_MASTER/$DOM
            then
                $sed "s/$PAT/$DOMLINE/" < $NAMED_MASTER/$DOM > $NAMED_MASTER/$DOM.$$
                mv $NAMED_MASTER/$DOM.$$ $NAMED_MASTER/$DOM
            else
                echo "$DOMLINE" >> $NAMED_MASTER/$DOM
            fi
	    fix_master_perms $NAMED_MASTER/$DOM

        fi

}


#---------------
# Crée un sous-domaine au niveau disque,
# et dans les fichiers named.
# prend domaine, 
# type, valeur, sous-domaine en argument.
# Principe : la création est forcée,
# si le sub existe déjà, il est remplacé.
#---------------
function creer_sous_domaine
{
	DOM=$1
	TYP=$2
	VAL=$4
	SB=$3
	POINT="."
	
        detruire_sous_domaine "$DOM" "$SB"

        if [ "$SB" = "" ] ; then
                POINT=""
                modifier_ip_sous_domaine "$DOM" "$PUBLIC_IP"
        else
                if [ "$TYP" = "$TYPE_IP" ]; then
                        ip=$VAL
                else
                        ip=$PUBLIC_IP
                fi
                modifier_ip_sous_domaine "$DOM" "$ip" "$SB"
        fi
	
	if [ "$TYP" = "$TYPE_LOCAL" ] ; then
		# NOTE : ne pas virer le rm -f (le ln -sf est buggé)
		$rm -f "${HTTP_DNS}/${INITIALE_DOM}/${SB}${POINT}${DOM}"
		$ln -s "${HTML_HOME}/${INITIALE_USER}/${USER}${VAL}" "${HTTP_DNS}/${INITIALE_DOM}/${SB}${POINT}${DOM}"
	fi
	
	if [ "$TYP" = "$TYPE_WEBMAIL" ] ; then
		# NOTE : ne pas virer le rm -f (le ln -sf est bugg?)
		$rm -f "${HTTP_DNS}/${INITIALE_DOM}/${SB}${POINT}${DOM}"
		$ln -s "${WEBMAIL_ROOT}" "${HTTP_DNS}/${INITIALE_DOM}/${SB}${POINT}${DOM}"
	fi
	
	if [ "$TYP" = "$TYPE_URL" ] ; then
		mkdir -p "${HTTP_DNS}/redir/${INITIALE_DOM}/${SB}${POINT}${DOM}"
		$echo "RewriteEngine on
RewriteRule (.*) ${VAL}/\$1 [R,L]" > "${HTTP_DNS}/redir/${INITIALE_DOM}/${SB}${POINT}${DOM}/.htaccess"
		# NOTE : ne pas virer le rm -f (le ln -sf est buggé)
		$rm -f "${HTTP_DNS}/${INITIALE_DOM}/${SB}${POINT}${DOM}"
		$ln -s "${HTTP_DNS}/redir/${INITIALE_DOM}/${SB}${POINT}${DOM}" "${HTTP_DNS}/${INITIALE_DOM}/${SB}${POINT}${DOM}"
	fi
	
	if [ "$TYP" = "$TYPE_IP" ] ; then
		$rm -f "${HTTP_DNS}/${INITIALE_DOM}/${SB}${POINT}${DOM}"
		$rm -fr "${HTTP_DNS}/redir/${INITIALE_DOM}/${SB}${POINT}${DOM}"
	fi
}

#---------------
# Destruction d'un
# sous-domaine
#---------------
function detruire_sous_domaine
{
	DOM=$1
	SB=$2

	edom=`$echo "$DOM" | $sed 's/\./\\\./g'`
	esub=`$echo "$SB" | $sed 's/\([\*|\.]\)/\\\\\1/g'`
	epoint="\."
	
	
	if [ "$SB" = "" ] ; then
		POINT=""
		epoint=""
	fi

	if [ -f "$NAMED_MASTER/$DOM" ] ; then
		$sed -e "/^$esub[[:space:]]*IN[[:space:]]*A[[:space:]]/d" < "$NAMED_MASTER/$DOM" > "$NAMED_MASTER/$DOM.$$" && mv "$NAMED_MASTER/$DOM.$$" "$NAMED_MASTER/$DOM"
		fix_master_perms "$NAMED_MASTER/$DOM"
	fi

        initial_domain=`init_dom_letter "$DOM"`
	$rm -f "/var/alternc/apacheconf/$initial_domain/${SB}${POINT}${DOM}"
	$sed -e "/\/${esub}${epoint}${edom}\$/d" > /var/alternc/apacheconf/override_php.conf.$$ < /var/alternc/apacheconf/override_php.conf && \
		mv /var/alternc/apacheconf/override_php.conf.$$ /var/alternc/apacheconf/override_php.conf

	$rm -f "${HTTP_DNS}/${INITIALE_DOM}/${SB}${POINT}${DOM}"
	$rm -fr "${HTTP_DNS}/redir/${INITIALE_DOM}/${SB}${POINT}${DOM}"
	increment_serial "$NAMED_MASTER/$DOM"
}


#---------------
# création du fichier named
# si il n'existe pas.
# Prend le nom du domaine
# en argument.
#---------------
function creer_fic_named
{
	if ! [ -f $NAMED_MASTER/$1 ] ; then
		SERIAL=`$date +%Y%m%d`00
		$sed s/"@@DOMAINE@@"/"${1}"/g < $NAMED_MASTER/$NAMED_TPL | $sed s/"@@SERIAL@@"/$SERIAL/g > $NAMED_MASTER/"${1}"
		$cp -f $NAMED_ETC/$NAMED_CONF $NAMED_ETC/$NAMED_CONF.prec
                $sed s/"@@DOMAINE@@"/"${1}"/g >> $NAMED_ETC/$NAMED_CONF < $NAMED_ETC/$NAMED_TPL
		fix_master_perms $NAMED_ETC/$NAMED_CONF
		RESTART_NAMED="true"
	fi
}


#---------------
# Destruction des fichiers
# de conf named pour
# un domaine
#---------------
function detruire_fic_named
{
	if [ -f $NAMED_MASTER/$1 ] ; then
		$rm -f $NAMED_MASTER/"$1"
		$grep -v "\"$1\"" > $NAMED_ETC/$NAMED_CONF.tmp < $NAMED_ETC/$NAMED_CONF
		$cp -f $NAMED_ETC/$NAMED_CONF $NAMED_ETC/$NAMED_CONF.prec
		$mv -f $NAMED_ETC/$NAMED_CONF.tmp $NAMED_ETC/$NAMED_CONF
	fi
}

#---------------
# Modification du champ mx.

  # prend domaine et champ mx
# en arguments.
#---------------
function modifier_mx_domaine
{
    DOM=$1
    MX=$2
    PAT="^@*[[:space:]]*IN[[:space:]]*MX[[:space:]]*[[:digit:]]*[[:space:]].*\$"
    MXLINE="@ 	IN 	MX 	5 	$MX."
    # aller chercher le numéro de la ligne MX
    # XXX: comportement inconnu si plusieurs matchs ou MX commenté
    if $grep -q "$PAT" "$NAMED_MASTER/$DOM"
    then
        $sed "s/$PAT/$MXLINE/" < "$NAMED_MASTER/$DOM" > "$NAMED_MASTER/$DOM.$$" && mv "$NAMED_MASTER/$DOM.$$" "$NAMED_MASTER/$DOM"
    else
        echo "$MXLINE" >> "$NAMED_MASTER/$DOM"
    fi
   fix_master_perms "$NAMED_MASTER/$DOM"

    increment_serial "$NAMED_MASTER/${1}"
    RESTART_NAMED="true"
}


# ####################################################################
# Main program
# ####################################################################

# ------------------------------------------------------------
# CALL with NO argument : process pending domains / subdomains
# 
# si le cron précédent n'est pas
# terminé, on attend le suivant.
if [ -f $FIC_TMP ] ; then
	echo `$date` >> $FIC_LOG
	echo "ERREUR : cron précédent inachevé." >> $FIC_LOG
	$echo "`$date` : $0 : cron précédent inachevé."
	exit 1
fi

> $FIC_LOCK

SQL_RES=`$MYSQL_SELECT "SELECT m.login,b.domaine,b.mx,b.gesdns,b.gesmx,b.action INTO OUTFILE '$FIC_TMP' FROM domaines_standby b INNER JOIN membres m ON m.uid=b.compte ORDER BY b.action;" 2>&1`
RES=$?

if [ "$RES" != 0 ] ; then
	$echo `$date` >> $FIC_LOG
	$echo "$SQL_RES" >> $FIC_LOG
	$echo "`$date` : $0 : erreur à l'exécution de la requête de sélection des domaines à traiter : $SQL_RES"
	$rm -f $FIC_LOCK >> $FIC_LOG 2>&1
	$rm -f $FIC_TMP >> $FIC_LOG 2>&1
	$rm -f $FIC_TMP_SUB >> $FIC_LOG 2>&1
	exit 1
else
	SQL_RES=`$MYSQL_SELECT "SELECT m.login,b.domaine,b.sub,b.valeur,b.type,b.action INTO OUTFILE '$FIC_TMP_SUB' FROM sub_domaines_standby b INNER JOIN membres m ON m.uid=b.compte ORDER BY b.action desc;" 2>&1`
	RES=$?
	if [ "$RES" != 0 ] ; then
		$echo `$date` >> $FIC_LOG
		$echo "$SQL_RES" >> $FIC_LOG
		$echo "`$date` : $0 : erreur à l'exécution de la requête de sélection des sous-domaines à traiter : $SQL_RES"
			$rm -f $FIC_LOCK >> $FIC_LOG 2>&1
		$rm -f $FIC_TMP >> $FIC_LOG 2>&1
		$rm -f $FIC_TMP_SUB >> $FIC_LOG 2>&1
		exit 1
	else
		$rm -f $FIC_LOCK
		$MYSQL_DELETE "DELETE FROM domaines_standby;"
		$MYSQL_DELETE "DELETE FROM sub_domaines_standby;"
		RESTART_NAMED="false"
		> $FIC_TMP_OVERRIDE_PHP

		# On traite les domaines
		$sed s/"	"/"@"/g > $FIC_TMP.tmp < $FIC_TMP
		$mv -f $FIC_TMP.tmp $FIC_TMP
		
		if [ `$wc -l $FIC_TMP | $awk {'print $1'}` -gt 0 ] ; then
			$echo `$date` >> $FIC_LOG
			$cat $FIC_TMP >> $FIC_LOG
		fi

		for i in `$cat $FIC_TMP` ; do
			USER=`$echo "$i" | $cut -d"@" -f1`
			DOMAINE=`$echo "$i" | $cut -d"@" -f2`
			MX=`$echo "$i" | $cut -d"@" -f3 | $sed -e 's/\.*$//'`
			GESDNS=`$echo "$i" | $cut -d"@" -f4`
			GESMX=`$echo "$i" | $cut -d"@" -f5`
			ACTION=`$echo "$i" | $cut -d"@" -f6`
			PASS=`$echo "$i" | $cut -d"@" -f7`
			INITIALE_DOM=`$echo "$DOMAINE" | $awk '{z = split($0, intiale, "."); print substr(intiale[z - 1], 1, 1)}'`
			INITIALE_USER=`$echo "$USER" | $awk '{print substr($1, 1, 1)}'`

			echo "${DOMAINE}@${USER}" >> $FIC_TMP_OVERRIDE_PHP
			echo "www.${DOMAINE}@${USER}" >> $FIC_TMP_OVERRIDE_PHP

			if [ "$ACTION" = "$ACTION_INSERT" ] ; then
				# création des liens symboliques par défaut :
				# NOTE : ne pas virer le rm -f (le ln -sf est buggé)
				$rm -f "${HTTP_DNS}/${INITIALE_DOM}/$DOMAINE"
				$ln -s "${HTML_HOME}/${INITIALE_USER}/$USER" "${HTTP_DNS}/${INITIALE_DOM}/$DOMAINE"
				$rm -f "${HTTP_DNS}/${INITIALE_DOM}/www.$DOMAINE"
				$ln -s "${HTML_HOME}/${INITIALE_USER}/$USER" "${HTTP_DNS}/${INITIALE_DOM}/www.$DOMAINE"
				$rm -f "${HTTP_DNS}/${INITIALE_DOM}/mail.$DOMAINE"
				$ln -s "${WEBMAIL_ROOT}" "${HTTP_DNS}/${INITIALE_DOM}/mail.$DOMAINE"
				
				if [ "$GESDNS" = "$OUI" ] ; then
					creer_fic_named "$DOMAINE"
					modifier_ip_sous_domaine "$DOMAINE" "$PUBLIC_IP"
					modifier_ip_sous_domaine "$DOMAINE" "$PUBLIC_IP" www
					modifier_ip_sous_domaine "$DOMAINE" "$PUBLIC_IP" mail
				fi
			fi
			
			if [ "$ACTION" = "$ACTION_UPDATE" ] ; then
				if [ "$GESDNS" = "$OUI" ] ; then
					creer_fic_named "$DOMAINE"
					modifier_mx_domaine "$DOMAINE" "$MX"
				else
					detruire_fic_named "$DOMAINE"
				fi
			fi
			
			if [ "$ACTION" = "$ACTION_DELETE" ] ; then
				detruire_fic_named "$DOMAINE"
				# suppression des liens symboliques :
				$rm -f "${HTTP_DNS}/${INITIALE_DOM}/"*".$DOMAINE"
				$rm -f "${HTTP_DNS}/${INITIALE_DOM}/$DOMAINE"
				$rm -fr "${HTTP_DNS}/redir/${INITIALE_DOM}/"*".$DOMAINE"
				$rm -fr "${HTTP_DNS}/redir/${INITIALE_DOM}/$DOMAINE"
			fi
			
			RESTART_NAMED="true"
		done

		# on traite les sous-domaines
	        $sed s/"	"/"@"/g > $FIC_TMP_SUB.tmp < $FIC_TMP_SUB
		$mv -f $FIC_TMP_SUB.tmp $FIC_TMP_SUB
		fix_master_perms "$FIC_TMP_SUB"
	
		if [ `$wc -l $FIC_TMP_SUB | $awk {'print $1'}` -gt 0 ] ; then
			$echo `$date` >> $FIC_LOG_SUB
			$cat $FIC_TMP_SUB >> $FIC_LOG_SUB
		fi
			
		for i in `$cat $FIC_TMP_SUB` ; do
			USER=`$echo "$i" | $cut -d"@" -f1`
			DOMAINE=`$echo "$i" | $cut -d"@" -f2`
			SUB=`$echo "$i" | $cut -d"@" -f3`
			VALEUR=`$echo "$i" | $cut -d"@" -f4`
			TYPE=`$echo "$i" | $cut -d"@" -f5`
			ACTION=`$echo "$i" | $cut -d"@" -f6`
			PASS=`$echo "$i" | $cut -d"@" -f7`
			INITIALE_DOM=`$echo "$DOMAINE" | $awk '{z = split($0, intiale, "."); print substr(intiale[z - 1], 1, 1)}'`
			INITIALE_USER=`$echo "$USER" | $awk '{print substr($1, 1, 1)}'`
			
			POINT="."
			[ "$SUB" = "" ] && POINT=""
			$echo "${SUB}${POINT}${DOMAINE}@${USER}" >> $FIC_TMP_OVERRIDE_PHP

			if [ "$ACTION" = "$ACTION_UPDATE" -o "$ACTION" = "$ACTION_INSERT" ] ; then
				creer_sous_domaine "$DOMAINE" "$TYPE" "$SUB" "$VALEUR" 
			fi
			
			if [ "$ACTION" = "$ACTION_DELETE" ] ; then
				detruire_sous_domaine "$DOMAINE" "$SUB"
			fi
			
			RESTART_NAMED="true"
		done

		# On crée ou supprime les fichiers /etc/apache/override_php/... pour les domaines modifiés
		# C'est un patch pour éviter que les users ne puissent utiliser opendir()
		# pour voir/modifier les autres comptes que le leur.
		for i in `$sort -u < $FIC_TMP_OVERRIDE_PHP` ; do
			domain=`$echo "$i" | $cut -d"@" -f1`
			/usr/lib/alternc/basedir_prot.sh "$domain" > /dev/null
		done


		# redémarrage apache et bind si nécessaire	
		if [ "$RESTART_NAMED" = "true" ] ; then
			$RELOAD_NAMED reload
			# ne pas faire de killall -1 apache, car tous les streamings et downloads en cours seraient tués. Donc un reload :
			$RELOAD_APACHE reload > /dev/null 2>&1
		fi
		
		$rm -f $FIC_TMP_SUB >> $FIC_LOG 2>&1
		$rm -f $FIC_TMP >> $FIC_LOG 2>&1
		$rm -f $FIC_TMP_OVERRIDE_PHP >> $FIC_LOG 2>&1
	fi
fi	

	


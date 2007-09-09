#!/bin/sh

set -e

# Ceci créé un hack php pour chacun des domaines hébergés par alternc
# ce hack consiste à restreindre chaque usager à son propre répertoire
# dans alternc/html/u/user avec open_base_dir

# ce script a les dépendances suivantes:
# (mysql, /etc/alternc/local.sh) OR /usr/bin/get_account_by_domain dans
# l'ancien package alternc-admintools désormais dans alternc natif.
# cut, awk, sort

override_d=/var/alternc/apacheconf
override_f=${override_d}/override_php.conf
extra_paths="/var/alternc/dns/redir:/usr/share/php/:/var/alternc/tmp/:/tmp/"

. /etc/alternc/local.sh
. /usr/lib/alternc/functions.sh

if [ -z "$MYSQL_HOST" ]
then
    MYSQL_HOST="localhost"
fi

echo -n "adding open_base_dir protection for:"
# boucle sur tous les domaines hébergés, ou sur les arguments de la
# ligne de commande
if [ $# -gt 0 ]; then
	for i in "$*"
        do
                if echo "$i" | grep -q '^\*\.'
                then
                    echo skipping wildcard "$i" >&2
                    continue
                fi
		if echo "$i" | grep -q /var/alternc/dns > /dev/null; then
			dom="$i"
		else
		    initial_domain=`print_domain_letter "$i"`
		    dom="/var/alternc/dns/$initial_domain/$i"
		fi
		doms="$doms $dom"
	done
else
	doms=`find /var/alternc/dns -type l`
fi

for i in $doms
do
	# don't "protect" squirrelmail, it legitimatly needs to consult
	# files out of its own directory
	if readlink "$i" | grep -q '^/var/alternc/bureau/admin/webmail/*$' || \
	   readlink "$i" | grep -q '^/var/alternc/bureau/*$'
	then
		continue
	fi
	domain=`basename "$i"`
	account=`get_account_by_domain $domain`
	if [ -z "$account" ]; then
		continue
	fi
	# la première lettre de l'avant-dernière partie du domaine (e.g.
	# www.alternc.org -> a)
	initial_domain=`print_domain_letter "$domain"`
	# la première lettre du username
	initial_account=`print_user_letter "$account"`
	path1="/var/alternc/dns/$initial_domain/$domain"
	path2="/var/alternc/html/$initial_account/$account"

	mkdir -p "$override_d/$initial_domain"
	if append_no_dupe "$override_d/$initial_domain/$domain" <<EOF
<Directory ${path1}>
  php_admin_value open_basedir ${path2}/:${extra_paths}
</Directory>
EOF
	then
		true
	else
		echo -n " $domain"
		add_dom_entry "Include $override_d/$initial_domain/$domain"
	fi
done

echo .

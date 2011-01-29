#!/bin/bash
# Update domain next-gen by fufrom

for CONFIG_FILE in \
      /etc/alternc/local.sh \
      /usr/lib/alternc/functions.sh \
      /usr/lib/alternc/hosting_functions_v2.sh \
      /usr/lib/alternc/dns.sh
  do
    if [ ! -r "$CONFIG_FILE" ]; then
        echo "Can't access $CONFIG_FILE."
        exit 1
    fi
    . "$CONFIG_FILE"
done

# Some vars
umask 022
LOCK_FILE="$ALTERNC_LOC/bureau/cron.lock"


# Somes check before start operations
if [ `id -u` -ne 0 ]; then
    log_error "must be launched as root"
elif [ -z "$DEFAULT_MX" -o -z "$PUBLIC_IP" ]; then
    log_error "Bad configuration. Please use: dpkg-reconfigure alternc"
elif [ -f "$LOCK_FILE" ]; then
    log_error "last cron unfinished or stale lock file ($LOCK_FILE)."
fi

# backward compatibility: single-server setup
if [ -z "$ALTERNC_SLAVES" ] ; then
    ALTERNC_SLAVES="localhost"
fi

# We lock the application
touch "$LOCK_FILE"

# For domains we want to delete completely, make sure all the tags are all right
# set sub_domaines.web_action = delete where domaines.dns_action = DELETE
$MYSQL_DO "update sub_domaines sd, domaines d set sd.web_action = 'DELETE' where sd.domaine = d.domaine and sd.compte=d.compte and d.dns_action = 'DELETE';"

# Sub_domaines we want to delete
# sub_domaines.web_action = delete
for sub in $( $MYSQL_DO "select concat_ws('|µ',if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine),sd.type) from sub_domaines sd where web_action ='DELETE';") ; do
    host_delete $(echo $sub|tr '|µ' ' ')
    # TODO Update the entry in the DB with the result and the action
done

# Sub domaines we want to update
# sub_domaines.web_action = update and sub_domains.only_dns = false
params=$( $MYSQL_DO "
  select concat_ws('|µ',lower(sd.type), if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine), valeur) 
  from sub_domaines sd
  where sd.web_action ='UPDATE'
  ;")
for sub in $params;do
    host_create $(echo $sub|tr '|µ' ' ')
    $MYSQL_DO "update sub_domaines sd set web_action='OK',web_result='$?' where concat_ws('|µ',lower(sd.type),if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine),valeur)='$sub'"
done

# Domaine to enable
for sub in $( $MYSQL_DO "select concat_ws('|µ',if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine),lower(sd.type)) from sub_domaines sd where sd.enable ='ENABLE' ;");do
    host_enable $(echo $sub|tr '|µ' ' ')
    $MYSQL_DO "update sub_domaines sd set enable='ENABLED' where concat_ws('|µ',if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine),lower(sd.type)) = '$sub';"
done

# Domains to disable
for sub in $( $MYSQL_DO "select concat_ws('|µ',if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine),lower(sd.type)) from sub_domaines sd where sd.enable ='DISABLE' ;");do
    host_disable $(echo $sub|tr '|µ' ' ')
    $MYSQL_DO "update sub_domaines sd set enable='DISABLED' where concat_ws('|µ',if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine),lower(sd.type)) = '$sub';"
done

# Domains we do not want to be the DNS serveur anymore :
# domaines.dns_action = UPDATE and domaines.gesdns = 0
for dom in $( $MYSQL_DO "select domaine from domaines where dns_action = 'UPDATE' and gesdns = 0;") ; do
    dns_delete $dom
    $MYSQL_DO "update domaines set dns_action = 'OK', dns_result = '$?' where domaine = '$dom'"
done

# Domains we have to update the dns :
# domaines.dns_action = UPDATE
for dom in $( $MYSQL_DO "select domaine from domaines where dns_action = 'UPDATE';") ; do
    dns_regenerate $dom
    $MYSQL_DO "update domaines set dns_action = 'OK', dns_result = '$?' where domaine = '$dom'"
done

# Domains we want to delete completely, now we do it
# domaines.dns_action = DELETE
for dom in $( $MYSQL_DO "select domaine from domaines where dns_action = 'DELETE';") ; do
    dns_delete $dom
    # Web configurations have already bean cleaned previously
    $MYSQL_DO "delete sub_domaines where domaine='$dom'; delete domaines where domaine='$dom';"
done


# Concat the apaches files
tempo=$(mktemp /tmp/alternc-vhost.XXXXX)
find "$VHOST_DIR" -mindepth 2 -type f -iname "*.conf" -exec cat '{}' > "$tempo" \;
if [ $? -ne 0 ] ; then
  log_error " web file concatenation failed"
fi
touch "$VHOST_FILE"
if [ ! -w "$VHOST_FILE" ] ; then
  log_error "cannot write on $VHOST_FILE"
fi

mv "$tempo" "$VHOST_FILE"

echo Exitbefore reload everything, we are testing, FUCK
rm "$LOCK_FILE"
exit 1

# Reload web and dns
alternc_reload all

# TODO reload slaves

rm "$LOCK_FILE"

exit 0



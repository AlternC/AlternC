#!/bin/bash
# Update domain next-gen by fufroma
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

for CONFIG_FILE in \
      /etc/alternc/local.sh \
      /usr/lib/alternc/functions.sh \
      /usr/lib/alternc/functions_hosting.sh \
      /usr/lib/alternc/functions_dns.sh
  do
    if [ ! -r "$CONFIG_FILE" ]; then
        echo "Can't access $CONFIG_FILE."
        exit 1
    fi
    . "$CONFIG_FILE"
done

stop_if_jobs_locked

# Some vars
umask 022
LOCK_FILE="/usr/share/alternc/panel/cron.lock" # FIXME doesn't seem clean to be here
OLDIFS="$IFS"
NEWIFS=" "
LOGFORMAT_FILE="/etc/alternc/apache_logformat.conf"
RELOAD_WEB="$(mktemp /tmp/alternc_reload_web.XXXX)"
RELOAD_DNS="$(mktemp /tmp/alternc_reload_dns.XXXX)"
B="µµ§§" # Strange letters to make split in query

# Somes check before start operations
if [ `id -u` -ne 0 ]; then
    log_error "must be launched as root"
elif [ -z "$DEFAULT_MX" -o -z "$PUBLIC_IP" ]; then
    log_error "Bad configuration. Please use: dpkg-reconfigure alternc"
elif [ -f "$LOCK_FILE" ]; then
    process=$(ps f -p `cat "$LOCK_FILE"|tail -1`|tail -1|awk '{print $NF;}')
    if [ "$(basename $process)" = "$(basename "$0")" ] ; then
      log_error "last cron unfinished or stale lock file ($LOCK_FILE)."
    else
      rm "$LOCK_FILE"
    fi
fi

# backward compatibility: single-server setup
if [ -z "$ALTERNC_SLAVES" ] ; then
    ALTERNC_SLAVES="localhost"
fi

# We lock the application
echo $$ > "$LOCK_FILE"

echo "" > "$RELOAD_WEB"
echo "" > "$RELOAD_DNS"

# For domains we want to delete completely, make sure all the tags are all right
# set sub_domaines.web_action = delete where domaines.dns_action = DELETE
mysql_query "update sub_domaines sd, domaines d set sd.web_action = 'DELETE' where sd.domaine = d.domaine and sd.compte=d.compte and d.dns_action = 'DELETE';"

# Sub_domaines we want to delete
# sub_domaines.web_action = delete
for sub in $( mysql_query "select concat_ws('$B',lower(sd.type), if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine)) from sub_domaines sd where web_action ='DELETE';") ; do
    host_delete ${sub/$B/ }
    mysql_query "delete from sub_domaines where concat_ws('$B',lower(type), if(length(sub)>0,concat_ws('.',sub,domaine),domaine)) = '$sub' and web_action ='DELETE';"
    echo 1 > "$RELOAD_WEB"
done

# Sub domaines we want to update
# sub_domaines.web_action = update and sub_domains.only_dns = false
IFS="$NEWIFS"
mysql_query "
select concat_ws('$IFS',sd.id, lower(sd.type), if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine), concat_ws('@',m.login,v.value), sd.valeur )
from sub_domaines sd,membres m,variable v
where sd.compte=m.uid and sd.web_action ='UPDATE' and v.name='mailname_bounce'
;" | while read sdid type domain mail valeur ; do
    host_create "$type" "$domain" "$mail" "$valeur"
    mysql_query "update sub_domaines sd set web_action='OK',web_result='$?' where sd.id = '$sdid' ; "
    echo 1 > "$RELOAD_WEB"
done

# Domaine to enable
mysql_query "select concat_ws('$IFS',sd.id, lower(sd.type),if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine),sd.valeur) from sub_domaines sd where sd.enable ='ENABLE' ;"|while read sdid type domain valeur ; do
    host_enable "$type" "$domain" "$valeur"
    mysql_query "update sub_domaines sd set enable='ENABLED' where sd.id = '$sdid' ;"
    echo 1 > "$RELOAD_WEB"
done

# Domains to disable
mysql_query "select concat_ws('$IFS', sd.id, lower(sd.type),if(length(sd.sub)>0,concat_ws('.',sd.sub,sd.domaine),sd.domaine),sd.valeur) from sub_domaines sd where sd.enable ='DISABLE' ;"|while read sdid type domain valeur ; do
    host_disable "$type" "$domain" "$valeur"
    mysql_query "update sub_domaines sd set enable='DISABLED' where sd.id = '$sdid' ;"
    echo 1 > "$RELOAD_WEB"
done

# Domains we do not want to be the DNS serveur anymore :
# domaines.dns_action = UPDATE and domaines.gesdns = 0
for dom in `mysql_query "select domaine from domaines where dns_action = 'UPDATE' and gesdns = 0;"| tr '\n' ' '`
do
    dns_delete $dom
    mysql_query "update domaines set dns_action = 'OK', dns_result = '$?' where domaine = '$dom'"
    echo 1 >"$RELOAD_DNS"
done

# Domains we have to update the dns :
# domaines.dns_action = UPDATE
for dom in `mysql_query "select domaine from domaines where dns_action = 'UPDATE';" | tr '\n' ' '`
do
    echo "dns_regenerate : domain=/$dom/"
    dns_regenerate $dom
    mysql_query "update domaines set dns_action = 'OK', dns_result = '$?' where domaine = '$dom'"
    echo 1 >"$RELOAD_DNS"
done

# Domains we want to delete completely, now we do it
# domaines.dns_action = DELETE
for dom in `mysql_query "select domaine from domaines where dns_action = 'DELETE';" | tr '\n' ' '`
do
    dns_delete $dom
    # Web configurations have already bean cleaned previously
    mysql_query "delete from sub_domaines where domaine='$dom'; delete from domaines where domaine='$dom';"
    echo 1 >"$RELOAD_DNS"
done

if [ ! -z "$(cat "$RELOAD_WEB")" ] ; then

  # Just to encourage user to use THIS directory and not another one
  test -d "$VHOST_MANUALCONF" || mkdir -p "$VHOST_MANUALCONF"

  # Concat the apaches files
  tempo=$(mktemp "$VHOST_FILE.XXXXX")

  (
    echo "###BEGIN OF ALTERNC AUTO-GENERATED FILE - DO NOT EDIT MANUALLY###"
    # If exists and readable, include conf file "apache_logformat.conf"
    # contain LogFormat and CustomLog directives for our Vhosts)
    echo "## LogFormat informations"
    if [ ! -r "$LOGFORMAT_FILE" ] ; then
      echo "## Warning : Cannot read $LOGFORMAT_FILE"
    else
      echo "Include \"$LOGFORMAT_FILE\""
    fi
    find "$VHOST_DIR" -mindepth 2 -type f -iname "*.conf" -print0 | xargs -0 cat 
    echo "###END OF ALTERNC AUTO-GENERATED FILE - DO NOT EDIT MANUALLY###" 
  ) > "$tempo"

  if [ $? -ne 0 ] ; then
    log_error " web file concatenation failed"
  fi
  touch "$VHOST_FILE"
  if [ ! -w "$VHOST_FILE" ] ; then
    log_error "cannot write on $VHOST_FILE"
  fi
  mv "$tempo" "$VHOST_FILE"

  # We must reload apache
  # we assume we run apache on the master
  /usr/lib/alternc/alternc_reload apache || true
  # Launch hooks for apache reload
  run-parts --arg=web_reload /usr/lib/alternc/reload.d
fi

# If we added / edited / deleted at least one dns zone file, we go here in the end:
if [ ! -z "$(cat "$RELOAD_DNS")" ] ; then
    service opendkim restart
    run-parts --arg=dns_reload /usr/lib/alternc/reload.d
fi

## FIXME : move the slave part into the /usr/lib/alternc/reload.d directory to be an hook
#for slave in $ALTERNC_SLAVES; do
#    if [ "$slave" != "localhost" ]; then
#        ssh alternc@$slave alternc_reload 'apache' || true
#    fi
#done

rm -f "$LOCK_FILE" "$RELOAD_ZONES" "$RELOAD_WEB" "$INOTIFY_UPDATE_DOMAIN" "$RELOAD_DNS"

exit 0


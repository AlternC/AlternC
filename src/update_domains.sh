#!/bin/bash
# Update domain next-gen by fufroma

for CONFIG_FILE in \
      /etc/alternc/local.sh \
      /usr/lib/alternc/functions.sh \
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

# For domains we want to delete completely, make sure all the tags are all right
# set sub_domaines.web_action = delete where domaines.dns_action = DELETE
mysql_query "update sub_domaines sd, domaines d set sd.web_action = 'DELETE' where sd.domaine = d.domaine and sd.compte=d.compte and d.dns_action = 'DELETE';"

# Launc apache script. If the script said so, reload apache.
if [ $(/usr/lib/alternc/generate_apache_conf.php) -gt 0 ] ; then

  # We must reload apache
  # we assume we run apache on the master
  /usr/lib/alternc/alternc_reload apache || true

  # Launch hooks for apache reload
  # In this directory, you can add you remote web server control
  run-parts --arg=web_reload /usr/lib/alternc/reload.d
fi

# Do bind updates
/usr/lib/alternc/generate_bind_conf.php

rm -f "$LOCK_FILE" "$RELOAD_ZONES" "$INOTIFY_UPDATE_DOMAIN"

exit 0


#!/bin/bash

# FIXME relecture + commentaires

for CONFIG_FILE in \
      /etc/alternc/local.sh \
      /usr/lib/alternc/functions.sh 
  do
    if [ ! -r "$CONFIG_FILE" ]; then
        echo "Can't access $CONFIG_FILE."
        exit 1
    fi
    . "$CONFIG_FILE"
done

stop_if_jobs_locked

max_process=2

tasks () {
$MYSQL_DO "select id, url, if(length(email)>0,email,'null'), schedule, UNIX_TIMESTAMP(), user, password as now from cron c where next_execution <= now();" | while read id url email schedule now user password ; do
  echo $id $url \"$email\" $schedule $now \"$user\" \"$password\" 
done
}

tasks | xargs -n 7 -P $max_process --no-run-if-empty /usr/lib/alternc/cron_users_doit.sh 

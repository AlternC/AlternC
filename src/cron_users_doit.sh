#!/bin/bash

# FIXME relecture + commentaires

id=$1
url=$2
email=$3
schedule=$4
now=$5
user=$6
password=$7

timeout=5

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

if [ "x$url" == "x" ] ; then
  echo Missing arguments
  exit 0
fi

urldecode() {
  arg="$1"
  i="0"
(
  while [ "$i" -lt ${#arg} ]; do
    c0=${arg:$i:1}
    if [ "x$c0" = "x%" ]; then
      c1=${arg:$((i+1)):1}
      c2=${arg:$((i+2)):1}
      printf "\x$c1$c2"
      i=$((i+3))
    else
      echo -n "$c0"
      i=$((i+1))
    fi
  done
) | sed -e 's/"/\\"/g' -e 's/\!/\\\!/g'
}

params=""
if [ ! "x$user" == "x" -a ! "x$password" == "x" ]; then
  params="--http-user=\"$(urldecode $user)\" --http-password=\"$(urldecode $password)\""
fi

echo wget -O - $params "$(urldecode $url)" --timeout=$timeout
# FIXME envoie de mail pour le "rapport d'execution"

# On calcule l'heure de la prochaine execution idéale
((interval=$schedule * 60))
((next=$(( $(( $now / $interval)) + 1 )) * $interval ))

# On check pour pas avoir d'injection SQL
if [[ ! "$id" =~ ^[0-9]+$ || ! "$next" =~ ^[0-9]+$ ]] ; then
  echo "Id +$id+ or time +$next+ is incorrect."
  return 2
fi

$MYSQL_DO "update cron set next_execution = FROM_UNIXTIME($next) where id = $id;"


#!/bin/bash
# functions.sh next-gen by Fufroma

# Init some vars
. /etc/alternc/local.sh

# Init some other vars
MYSQL_DO="/usr/bin/mysql --defaults-file=/etc/alternc/my.cnf -Bs -e "
mysql_query() { /usr/bin/mysql --defaults-file=/etc/alternc/my.cnf -Bs -e "$@" ; }
DOMAIN_LOG_FILE="/var/log/alternc/update_domains.log"
VHOST_FILE="$VHOST_DIR/vhosts_all.conf" 

# Some usefull miscellaneous shell functions
print_domain_letter() {
    local domain="$1"

    local letter=`echo "$domain" | awk '{z=split($NF, a, ".") ; print substr(a[z-1], 1, 1)}'`
    if [ -z "$letter" ]; then
      letter="_"
    fi
    echo $letter
}

print_user_letter() {
    local user="$1"
    echo "$user" | awk '{print substr($1, 1, 1)}'
}

# imprime le nom d'usager associé au domaine
get_account_by_domain() {
    # les admintools ne sont peut-être pas là
#    if [ -x "/usr/bin/get_account_by_domain" ]
#    then
#        # only first field, only first line
#        /usr/bin/get_account_by_domain "$1"|head -1|awk '{ print $1;}'
#    else
        # implantons localement ce que nous avons besoin, puisque admintools
        # n'est pas là
        mysql_query 'SELECT a.login FROM membres a, sub_domaines b WHERE a.uid = b.compte AND \
        CONCAT(IF(sub="", "", CONCAT(sub, ".")), domaine) = "'"$1"'" LIMIT 1;'
#    fi
}

log_error() {
  local error=$1
  echo "`date` $0 : $1" | tee -a "$DOMAIN_LOG_FILE" >&2
  exit 1
}


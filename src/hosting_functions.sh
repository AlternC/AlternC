HOST_DIR="/etc/apache2/sites-available"
TEMPLATE_DIR="/etc/alternc/templates/apache2"
DATA_ROOT="/var/alternc"

HTML_HOME="$DATA_ROOT/html"

HOSTING_DIR="/usr/lib/alternc/hosting_functions"


. /usr/lib/alternc/functions.sh

if [ -d $HOSTING_DIR ]; then
  for i in $HOSTING_DIR/*.sh; do
    if [ -r $i ]; then
      . $i
    fi
  done
  unset i
fi



host_prepare_conf() {
    local TEMPLATE=$1
    HOST_TMP=`mktemp`

    cp $TEMPLATE_DIR"/"$TEMPLATE $HOST_TMP
}

host_save_conf() {

    local SOURCE=$1
    local TARGET=$2

    TARGET_DIR=`dirname $TARGET`
    mkdir -p $TARGET_DIR
    mv $SOURCE $TARGET
}


host_enable_host() {
    local USER=$1
    local FQDN=$2
    
    local FILE_TARGET="/etc/apache2/sites-enabled/"$FQDN
    local FILE_SOURCE=$HOST_DIR"/"$USER"/"$FQDN
    
    if [ -L "$FILE_TARGET" ]; then
        rm $FILE_TARGET
    fi
    
    ln -s $FILE_SOURCE $FILE_TARGET
}

host_disable_host() {
    local FQDN=$1
    local CONF_FILE="/etc/apache2/sites-enabled/"$FQDN

    if [ -e "$CONF_FILE" ]; then
        rm $CONF_FILE
    fi
}

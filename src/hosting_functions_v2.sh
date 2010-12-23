TEMPLATE_DIR="/etc/alternc/templates/apache2"
HOSTING_DIR="/usr/lib/alternc/hosting_functions"

DATA_ROOT="/var/alternc"
HTML_HOME="$DATA_ROOT/html"
VHOST_DIR="$DATA_ROOT/apache-vhost"
VHOST_FILE="$VHOST_DIR/vhosts_all.conf"

. /usr/lib/alternc/functions.sh

host_create() {
    # Function to create a vhost for a website
    # First, it look if there is a special file for
    # this type of vhost
    # If there isn't, it use the default function
    # and the template file provided

    local VTYPE="$1"
 
    if [ -x "$HOSTING_DIR/hosting_$VTYPE.sh" ] ; then
        # There is a script special for this type,
        # I launch it and quit the host_create function
        # (I precise to the script this is for a "enable" task)
        "$HOSTING_DIR/hosting_$VTYPE.sh" "create" $@
        return
    fi
    
    # There is no special script, I use the standart template
    # If I do not found template manualy define, I look
    # If there is an existing template with the good name

    # First, usefull vars. Some may be empty or false, it's
    # OK, it will be solve in the "case" below
    local USER=$2
    local FQDN=$3
    local REDIRECT=$4   # Yes, TARGET_DIR and REDIRECT are the same
    local TARGET_DIR=$4 # It's used by different template
    local user_letter=`print_user_letter "$USER"`
    local DOCUMENT_ROOT="${HTML_HOME}/${user_letter}/${USER}/$TARGET_DIR"
    local FILE_TARGET="$VHOST_DIR/${user_letter}/$USER/$FQDN.conf"


    # panel.conf  redirect.conf  vhost.conf  webmail.conf
    case $VTYPE in
      "vhost")
        TEMPLATE="$TEMPLATE_DIR/vhost.conf"
        ;;
      *)
        # No template found, look if there is some in the
        # template dir
        [ -r "$TEMPLATE_DIR/$VTYPE" ] && TEMPLATE="$TEMPLATE_DIR/$VTYPE"
        [ ! "$TEMPLATE" ] && [ -r "$TEMPLATE_DIR/$VTYPE.conf" ] && TEMPLATE="$TEMPLATE_DIR/$VTYPE.conf"
        ;;
    esac

    # Create a new conf file
    local TMP_FILE=$(mktemp "/tmp/alternc_host.XXXXXX")
    cp "$TEMPLATE" "$TMP_FILE"
    echo "#Username: $USER"

    # Put the good value in the conf file
        sed -i \
        -e "s#%%fqdn%%#$FQDN#g" \
        -e "s#%%document_root%%#$DOCUMENT_ROOT#g" \
        -e "s#%%redirect%%#$REDIRECT#g" \
        $TMP_FILE

    # Check if all is right in the conf file
    # If not, put a debug message
    local ISNOTGOOD=$(grep "%%" "$TMP_FILE") 
    [ "$ISNOTGOOD" ] && (echo "# There was a probleme in the generation : $ISNOTGOOD" > "$TMP_FILE"

    # Put the conf file in prod
    mkdir -p "$(dirname "$FILE_TARGET")"
    mv -f "$TMP_FILE" "$FILE_TARGET"

}

host_disable() {
    host_change_enable "disable" $@
}

host_enable() {
    host_change_enable "enable" $@
}

host_change_enable() {
    # Function to enable or disable a host
    local STATE=$1 
    local FQDN=$2
    local USER=`get_account_by_domain $FQDN`
    local user_letter=`print_user_letter "$USER"`
    local FENABLED="$VHOST_DIR/${user_letter}/$USER/$FQDN.conf"
    local FDISABLED="$FENABLED-disabled"

    case $STATE in
        "enable")
            local SOURCE="$FDISABLED"
            local TARGET="$FENABLED"
            ;;
        "disable")
            local TARGET="$FDISABLED"
            local SOURCE="$FENABLED"
            ;;
        *)
            return 1
            ;;
    esac

    if [ ! -e "$TARGET" ] && [ -e "$SOURCE" ] ; then
        # If the "target" file do not exist and the "source" file exist
        rename -f "$SOURCE" "$TARGET"
    else
        return 2
    fi
}

host_delete() {
    local FQDN=$1
    local USER=`get_account_by_domain $FQDN`
    local user_letter=`print_user_letter "$USER"`
    local FENABLED="$VHOST_DIR/${user_letter}/$USER/$FQDN.conf"
    local FDISABLED="$FENABLED-disabled"

    [ -w "$FENABLED" ] && rm -f "$FENABLED"
    [ -w "$FDISABLED" ] && rm -f "$FDISABLED"
}



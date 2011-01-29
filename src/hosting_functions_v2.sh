#!/bin/bash

. /usr/lib/alternc/functions.sh

TEMPLATE_DIR="/etc/alternc/templates/apache2"
HOSTING_DIR="/usr/lib/alternc/hosting_functions"

HTML_HOME="$ALTERNC_LOC/html"
VHOST_DIR="$ALTERNC_LOC/apache-vhost"


host_create() {
    # Function to create a vhost for a website
    # First, it look if there is a special file for
    # this type of vhost
    # If there isn't, it use the default function
    # and the template file provided

    local VTYPE="$1"
 
    if [ -x "$HOSTING_DIR/hosting_$VTYPE.sh" ] ; then
        # There is a script special for this type,
        # I launch it and quit the host_create function
        # (I precise to the script this is for a "enable" task)
        "$HOSTING_DIR/hosting_$VTYPE.sh" "create" $@
        local returnval=$?

        # If the special script for this type exit with a code between
        # 20 and 25, it means I have to continue like it didn't exist.
        # It allow for example creation a script to exist only for deletion,
        # or to do pre-inst or post-inst.
        if [ $returnval -lt 20 ] || [ $returnval -gt 25 ] ; then
            return
        fi
    fi
    
    # There is no special script, I use the standart template
    # If I do not found template manualy define, I look
    # If there is an existing template with the good name

    # First, usefull vars. Some may be empty or false, it's
    # OK, it will be solve in the "case" below
    local FQDN=$2
    local REDIRECT=$3   # Yes, TARGET_DIR and REDIRECT are the same
    local TARGET_DIR=$3 # It's used by different template
    local USER=$(get_account_by_domain $FQDN)
    local user_letter=`print_user_letter "$USER"`
    local DOCUMENT_ROOT="${HTML_HOME}/${user_letter}/${USER}/$TARGET_DIR"
    local FILE_TARGET="$VHOST_DIR/${user_letter}/$USER/$FQDN.conf"

    # In case VTYPE don't have the same name as the template file, 
    # here we can define it
    local TEMPLATE=''
    case $VTYPE in
#      "example")
#        TEMPLATE="$TEMPLATE_DIR/an-example.conf"
#        ;;
      *)
        # No template found, look if there is some in the
        # template dir
        [ -r "$TEMPLATE_DIR/$VTYPE" ] && TEMPLATE="$TEMPLATE_DIR/$VTYPE"
        [ ! "$TEMPLATE" ] && [ -r "$TEMPLATE_DIR/$VTYPE.conf" ] && TEMPLATE="$TEMPLATE_DIR/$VTYPE.conf"
        ;;
    esac

    # If TEMPLATE is empty, stop right here
    [ ! "$TEMPLATE" ] && return 6

    # Create a new conf file
    local TMP_FILE=$(mktemp "/tmp/alternc_host.XXXXXX")
    cp "$TEMPLATE" "$TMP_FILE"

    # Put the good value in the conf file
        sed -i \
        -e "s#%%fqdn%%#$FQDN#g" \
        -e "s#%%document_root%%#$DOCUMENT_ROOT#g" \
        -e "s#%%redirect%%#$REDIRECT#g" \
        $TMP_FILE

    # Check if all is right in the conf file
    # If not, put a debug message
    local ISNOTGOOD=$(grep "%%" "$TMP_FILE") 
    [ "$ISNOTGOOD" ] && (echo "# There was a probleme in the generation : $ISNOTGOOD" > "$TMP_FILE" )

    # Put the conf file in prod
    mkdir -p "$(dirname "$FILE_TARGET")"
    mv -f "$TMP_FILE" "$FILE_TARGET"

    # Execute post-install if there is some for this VTYPE
    [ -x "$HOSTING_DIR/hosting_$VTYPE.sh" ] && "$HOSTING_DIR/hosting_$VTYPE.sh" "postint" $@

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

    # If there is a VTYPE precised and a specific script exist
    if [ $3 ] ; then 
        local VTYPE=$3
        if [ -x "$HOSTING_DIR/hosting_$VTYPE.sh" ] ; then
            "$HOSTING_DIR/hosting_$VTYPE.sh" $@
            return
        fi
    fi

    local FQDN=$2
    local USER=$(get_account_by_domain $FQDN)
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

    if [ ! -e "$TARGET" ] && [ -e "$SOURCE" ] ; then
        # If the "target" file do not exist and the "source" file exist
        rename -f "$SOURCE" "$TARGET"
    else
        return 2
    fi
}

host_delete() {
    local FQDN=$1

    # If there is a VTYPE precised and a specific script exist
    if [ $2 ] ; then 
        local VTYPE=$2
        if [ -x "$HOSTING_DIR/hosting_$VTYPE.sh" ] ; then
            "$HOSTING_DIR/hosting_$VTYPE.sh" "delete" $@
            local returnval=$?
            # If the exit value of the VTYPE script is between 20 and 25,
            # continue the delete like it didn't exist
            if [ $returnval -lt 20 ] || [ $returnval -gt 25 ] ; then
                return
            fi
        fi
    fi

    local USER=`get_account_by_domain $FQDN`
    local user_letter=`print_user_letter "$USER"`
    local FENABLED="$VHOST_DIR/${user_letter}/$USER/$FQDN.conf"
    local FDISABLED="$FENABLED-disabled"

    [ -w "$FENABLED" ] && rm -f "$FENABLED"
    [ -w "$FDISABLED" ] && rm -f "$FDISABLED"
}


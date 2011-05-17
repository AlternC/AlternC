#!/bin/bash

. /usr/lib/alternc/functions.sh

TEMPLATE_DIR="/etc/alternc/templates/apache2"
HOSTING_DIR="/etc/alternc/functions_hosting"

HTML_HOME="$ALTERNC_LOC/html"
VHOST_DIR="$ALTERNC_LOC/apache-vhost"

launch_hooks() {
  local ACTION=$1

  if [ ! $2 ] ; then
    # If no VTYPE specified
    return 0
  fi

  local VTYPE=$2

  if [ -x "$HOSTING_DIR/hosting_$VTYPE.sh" ] ; then
    # If a specific script exist for this VTYPE,
    # we launch it, and return his return code
    "$HOSTING_DIR/hosting_$VTYPE.sh" "$1" "$2" "$3" "$4" 
    return $?
  fi

  # No specific script, return 0
  return 0
}

host_create() {
    # Function to create a vhost for a website
    # First, it look if there is a special file for
    # this type of vhost
    # If there isn't, it use the default function
    # and the template file provided

    local VTYPE="$1"

    launch_hooks "create" "$1" "$2" "$3" "$4"
    if [ $? -gt 10 ] ; then
      # If the hooks return a value > 10
      # it's mean we do not continue the 
      # "default" actions
      return $?
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
    local U_ID=$(get_uid_by_name "$USER")
    local G_ID=$(get_uid_by_name "$USER")
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
        -e "s#%%UID%%#$U_ID#g" \
        -e "s#%%GID%%#$G_ID#g" \
        $TMP_FILE

    # Check if all is right in the conf file
    # If not, put a debug message
    local ISNOTGOOD=$(grep "%%" "$TMP_FILE") 
    [ "$ISNOTGOOD" ] && (echo "# There was a probleme in the generation : $ISNOTGOOD" > "$TMP_FILE" ; return 44 )

    # Put the conf file in prod
    mkdir -p "$(dirname "$FILE_TARGET")"
    mv -f "$TMP_FILE" "$FILE_TARGET"

    # Execute post-install hooks
    launch_hooks "postinst" "$1" "$2" "$3" "$4"
    if [ $? -gt 10 ] ; then
      # If the hooks return a value > 10
      # it's mean we do not continue the 
      # "default" actions
      return $?
    fi

    # All is quit, we return 0
    return 0
}

host_disable() {
    host_change_enable "disable" "$1" "$2" "$3" "$4"
}

host_enable() {
    host_change_enable "enable" "$1" "$2" "$3" "$4"
}

host_change_enable() {
    # Function to enable or disable a host
    local STATE=$1 

    # Execute hooks
    launch_hooks "$1" "$2" "$3" "$4"
    if [ $? -gt 10 ] ; then
      # If the hooks return a value > 10
      # it's mean we do not continue the 
      # "default" actions
      return $?
    fi

    local TYPE=$2 # no use here, but one day, maybe... So here he is
    local FQDN=$3
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
        mv -f "$SOURCE" "$TARGET"
    else
        return 2
    fi
}

host_delete() {
    local VTYPE=$1
    local FQDN=$2
    # Execute post-install hooks
    launch_hooks "delete" "$1" "$2" "$3" "$4"
    if [ $? -gt 10 ] ; then
      # If the hooks return a value > 10
      # it's mean we do not continue the 
      # "default" actions
      return $?
    fi

    local USER=`get_account_by_domain $FQDN`
    local user_letter=`print_user_letter "$USER"`
    local FENABLED="$VHOST_DIR/${user_letter}/$USER/$FQDN.conf"
    local FDISABLED="$FENABLED-disabled"

    [ -w "$FENABLED" ] && rm -f "$FENABLED"
    [ -w "$FDISABLED" ] && rm -f "$FDISABLED"
}


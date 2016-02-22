#!/bin/bash

. /usr/lib/alternc/functions.sh

TEMPLATE_DIR="/etc/alternc/templates/apache2"
HOSTING_DIR="/etc/alternc/functions_hosting"

HTML_HOME="$ALTERNC_HTML"
VHOST_DIR="/var/lib/alternc/apache-vhost"

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

host_conffile() {
    # Return the absolute path of a conf file for a FQDN
    local FQDN="$1"
    local U_ID=$(get_uid_by_domain "$FQDN")
    local CONFFILE="$VHOST_DIR/${U_ID:(-1)}/$U_ID/$FQDN.conf"
    echo $CONFFILE
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
    local MAIL_ACCOUNT=$3
    local REDIRECT=$4   # Yes, TARGET_DIR and REDIRECT are the same
    local TARGET_DIR=$4 # It's used by different template
    local U_ID=$(get_uid_by_domain "$FQDN")
    local G_ID="$U_ID"
    local USER=$(get_account_by_domain $FQDN)
    local user_letter=`print_user_letter "$USER"`
    local DOCUMENT_ROOT="${HTML_HOME}/${user_letter}/${USER}$TARGET_DIR"
    local ACCOUNT_ROOT="${HTML_HOME}/${user_letter}/${USER}/"
    local FILE_TARGET=$(host_conffile "$FQDN")

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

    # Forbid generation for website with UID/GID == 0
    if [[ $U_ID == 0 || $G_ID == 0 ]] ; then
      log_error "Fatal error: update_domains/function_dns/host_create : FQDN = $FQDN - TYPE = $VTYPE - UID = $U_ID - GID = $G_ID . Stopping generation"
      return 7
    fi

    # Create a new conf file
    local TMP_FILE=$(mktemp "/tmp/alternc_host.XXXXXX")
    cp "$TEMPLATE" "$TMP_FILE"

    # Substitute special characters : 
    FQDN2="`echo $FQDN | sed -e 's/\\\\/\\\\\\\\/g' -e 's/#/\\\\#/g' -e 's/&/\\\\\\&/g'`"
    DOCUMENT_ROOT2="`echo $DOCUMENT_ROOT | sed -e 's/\\\\/\\\\\\\\/g' -e 's/#/\\\\#/g' -e 's/&/\\\\\\&/g'`"
    ACCOUNT_ROOT2="`echo $ACCOUNT_ROOT | sed -e 's/\\\\/\\\\\\\\/g' -e 's/#/\\\\#/g' -e 's/&/\\\\\\&/g'`"	
    REDIRECT2="`echo $REDIRECT | sed -e 's/\\\\/\\\\\\\\/g' -e 's/#/\\\\#/g' -e 's/&/\\\\\\&/g'`"
    USER2="`echo $USER | sed -e 's/\\\\/\\\\\\\\/g' -e 's/#/\\\\#/g' -e 's/&/\\\\\\&/g'`"    

    # Put the good value in the conf file
        sed -i \
	-e "s#%%LOGIN%%#$USER#g" \
        -e "s#%%fqdn%%#$FQDN2#g" \
        -e "s#%%document_root%%#$DOCUMENT_ROOT2#g" \
        -e "s#%%account_root%%#$ACCOUNT_ROOT2#g" \
        -e "s#%%redirect%%#$REDIRECT2#g" \
        -e "s#%%UID%%#$U_ID#g" \
        -e "s#%%GID%%#$G_ID#g" \
        -e "s#%%mail_account%%#$MAIL_ACCOUNT#g" \
        -e "s#%%user%%#$USER2#g" \
        $TMP_FILE

    ## Fix for wildcard
    if [[ "$FQDN2" == "*."* ]]; then
       sed -i "s/ServerName/ServerAlias/" $TMP_FILE
    fi

    # Check if all is right in the conf file
    # If not, put a debug message
# NO : redirect and document_root COULD contains legitimate %% expressions (...) 
#    local ISNOTGOOD=$(grep "%%" "$TMP_FILE") 
#    [ "$ISNOTGOOD" ] && (echo "# There was a probleme in the generation : $ISNOTGOOD" > "$TMP_FILE" ; return 44 )

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
    local FENABLED=$(host_conffile "$FQDN")
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

    local FENABLED=$(host_conffile "$FQDN")
    local FDISABLED="$FENABLED-disabled"

    [ -w "$FENABLED" ] && rm -f "$FENABLED"
    [ -w "$FDISABLED" ] && rm -f "$FDISABLED"
}


host_create_vhost() {

    if [[ ( -z $1 ) || (-z $2 ) || ( -z $3) ]]; then 
        exit 1;
    fi

    echo "création vhost $1 pour $2, repertoire $3"

    local USER=$1
    local FQDN=$2
    local TEMPLATE="vhost.conf"
    local TARGET=$HOST_DIR"/"$USER"/"$FQDN

    local domain_letter=`print_domain_letter "$domain"`
    local user_letter=`print_user_letter "$user"`
    local DIRECTORY=${HTML_HOME}/${user_letter}/${user}$3

    host_prepare_conf $TEMPLATE #Return #HOST_TMP

    sed -i \
    -e "s#%%fqdn%%#$FQDN#g" \
    -e "s#%%document_root%%#$DIRECTORY#g" \
    $HOST_TMP

    host_save_conf $HOST_TMP $TARGET
}

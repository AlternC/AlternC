host_create_redirect() {

    if [[ ( -z $1 ) || (-z $2 ) || ( -z $3) ]]; then 
        exit 1;
    fi

    echo "création redirection pour $1 de $2 vers $3"

    local USER=$1
    local FQDN=$2
    local REDIRECT=$3
    local TEMPLATE="redirect.conf"
    local TARGET=$HOST_DIR"/"$USER"/"$FQDN
    host_prepare_conf $TEMPLATE #Return #HOST_TMP

    sed -i \
        -e "s#%%fqdn%%#${FQDN}#g" \
        -e "s#%%redirect%%#${REDIRECT}#g" \
        $HOST_TMP

    host_save_conf $HOST_TMP $TARGET

}

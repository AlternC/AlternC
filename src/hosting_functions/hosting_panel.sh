host_create_panel() {

    if [[ ( -z $1 ) || (-z $2 ) ]]; then 
        exit 1;
    fi

    echo "création panel $1"

    local USER=$1
    local FQDN=$2
    local TEMPLATE="panel.conf"
    local TARGET=$HOST_DIR"/"$USER"/"$FQDN
    host_prepare_conf $TEMPLATE #Return #HOST_TMP

    sed -i \
        -e "s#%%fqdn%%#$FQDN#g" \
        $HOST_TMP

    host_save_conf $HOST_TMP $TARGET
}

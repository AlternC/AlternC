#!/bin/bash
# dns.sh next-gen by Fufroma

# Init some vars
. /etc/alternc/local.sh
. /usr/lib/alternc/functions.sh

# Init some other vars
ZONE_TEMPLATE="/etc/alternc/templates/bind/templates/zone.template"
NAMED_TEMPLATE="/etc/alternc/templates/bind/templates/named.template"
NAMED_CONF="/var/alternc/bind/automatic.conf"

dns_zone_file() {
    echo "$ALTERNC_LOC/bind/zones/$1"
}

dns_is_locked() {
    local domain=$1
    if [ ! -r "$(dns_zone_file $domain)" ] ; then
      return 1
    fi
    grep "LOCKED:YES" "$(dns_zone_file $domain)"
    return $?
}

dns_get_serial() {
    local domain=$1
    local serial=$(( $(grep "; serial" $(dns_zone_file $domain) 2>/dev/null|awk '{ print $1;}') + 1 ))
    local serial2=$(date +%Y%m%d00)
    if [ $serial -gt $serial2 ] ; then
        echo $serial
    else
        echo $serial2
    fi
}

dns_chmod() {
    local domain=$1
    chgrp bind $(dns_zone_file $domain)
    chmod 640 $(dns_zone_file $domain)
    return 0
}

dns_named_conf() {
  local domain=$1

  if [ ! -f "$(dns_zone_file $domain)" ] ; then
    echo Error : no file $(dns_zone_file $domain)
    return 1
  fi

  grep -q "$domain" "$NAMED_CONF"
  if [ $? -ne 0 ] ; then
    local tempo=$(cat "$NAMED_TEMPLATE")
    tempo=${tempo/@@DOMAINE@@/$domain}
    tempo=${tempo/@@ZONE_FILE@@/$(dns_zone_file $domain)}
    echo $tempo >> "$NAMED_CONF"
  fi
}

dns_delete() {
  local domain=$1

  # Delete the zone file
  if [ -w $(dns_zone_file $domain) ] ; then
    rm -f $(dns_zone_file $domain)
  fi

  # Remove from the named conf
  local file=$(cat "$NAMED_CONF")
  echo -e "$file" |grep -v "\"$domain\"" > "$NAMED_CONF"
}

# DNS regenerate
dns_regenerate() {
    local domain=$1
    local manual_tag=";;; END ALTERNC AUTOGENERATE CONFIGURATION"
    local zone_file=$(dns_zone_file $domain)

    # Check if locked
    dns_is_locked "$domain"
    if [ $? -eq 0 ]; then
        echo "DNS $domain LOCKED" 
        return 1
    fi

    # Get the serial number if there is one
    local serial=$(dns_get_serial "$domain")

    # Generate the headers with the template
    local file=$(cat "$ZONE_TEMPLATE")

    # Add the entry
    file=$(
        echo -e "$file"
        $MYSQL_DO "select distinct replace(replace(dt.entry,'%TARGET%',sd.valeur), '%SUB%', if(length(sd.sub)>0,sd.sub,'@')) as entry from sub_domaines sd,domaines_type dt where sd.type=dt.name and sd.domaine='$domain' and sd.enable in ('ENABLE', 'ENABLED') order by entry ;"
    )

    # Get some usefull vars

# Deprecated ?
#    local mx=$( $MYSQL_DO "select mx from domaines where domaine='$domain' limit 1;")

    # Replace the vars by their values
    # Here we can add dynamic value for the default MX
    file=$( echo -e "$file" | sed -e "
            s/%%fqdn%%/$FQDN/g;
            s/%%ns1%%/$NS1_HOSTNAME/g;
            s/%%ns2%%/$NS2_HOSTNAME/g;
            s/%%DEFAULT_MX%%/$DEFAULT_MX/g;
            s/%%DEFAULT_SECONDARY_MX%%/$DEFAULT_SECONDARY_MX/g;
            s/@@fqdn@@/$FQDN/g;
            s/@@ns1@@/$NS1_HOSTNAME/g;
            s/@@ns2@@/$NS2_HOSTNAME/g;
            s/@@DEFAULT_MX@@/$DEFAULT_MX/g;
            s/@@DEFAULT_SECONDARY_MX@@/$DEFAULT_SECONDARY_MX/g;
            s/@@DOMAINE@@/$domain/g;
            s/@@SERIAL@@/$serial/g;
            s/@@PUBLIC_IP@@/$PUBLIC_IP/g")
    
    # Add the manual lines
    if [ -r "$zone_file" ] ; then
        file=$(
            echo -e "$file"
            grep -A 10000 "$manual_tag" "$zone_file"
            )
    else
        file=$(echo -e "$file"; echo "$manual_tag")
    fi

    # Init the file
    echo -e "$file" > "$zone_file"

    # And set his rights
    dns_chmod $domain
    # Add it to named conf
    dns_named_conf $domain
}

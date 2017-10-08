#!/bin/bash
# dns.sh next-gen by Fufroma

# Init some vars
. /etc/alternc/local.sh
. /usr/lib/alternc/functions.sh

# Init some other vars
ZONE_TEMPLATE="/etc/alternc/templates/bind/templates/zone.template"
NAMED_TEMPLATE="/etc/alternc/templates/bind/templates/named.template"
NAMED_CONF="/var/lib/alternc/bind/automatic.conf"
RNDC="/usr/sbin/rndc"

dns_zone_file() {
    echo "/var/lib/alternc/bind/zones/$1"
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

dns_get_zonettl() {
    local domain=$1
    local zonettl=$(
        $MYSQL_DO "SELECT zonettl FROM domaines d WHERE d.domaine='$domain';"
        )
    # default value
    if [ "$zonettl" == "" ] ; then
        zonettl="86400"
    fi
    if [ "$zonettl" -eq "0" ] ; then
        zonettl="86400"
    fi
    echo $zonettl
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

  # Add the entry
  grep -q "\"${domain/./\\.}\"" "$NAMED_CONF"
  if [ $? -ne 0 ] ; then
    local tempo=$(cat "$NAMED_TEMPLATE")
    tempo=${tempo/@@DOMAINE@@/$domain}
    tempo=${tempo/@@ZONE_FILE@@/$(dns_zone_file $domain)}
    echo $tempo >> "$NAMED_CONF"
    # Kindly ask Bind to reload its configuration
    # (the zone file is already created and populated)
    $RNDC reconfig
    # Hook it !
    run-parts --arg=dns_reconfig --arg="$domain" /usr/lib/alternc/reload.d
  fi

}

dns_delete() {
  local domain=$1

  # Delete the zone file
  if [ -w "$(dns_zone_file $domain)" ] ; then
    rm -f "$(dns_zone_file $domain)"
  fi

  local reg_domain=${domain/./\\.}

  # Remove from the named conf
  local file=$(cat "$NAMED_CONF")
  echo -e "$file" |grep -v "\"$reg_domain\"" > "$NAMED_CONF"

  # Remove the conf from openDKIM
  rm -rf "/etc/opendkim/keys/$domain"
  grep -v "^$reg_domain\$" /etc/opendkim/TrustedHosts >/etc/opendkim/TrustedHosts.alternc-tmp && mv /etc/opendkim/TrustedHosts.alternc-tmp /etc/opendkim/TrustedHosts
  grep -v "^alternc\._domainkey\.$reg_domain " /etc/opendkim/KeyTable >/etc/opendkim/KeyTable.alternc-tmp && mv /etc/opendkim/KeyTable.alternc-tmp /etc/opendkim/KeyTable
  grep -v "^$domain alternc\._domainkey\.$reg_domain\$" /etc/opendkim/SigningTable >/etc/opendkim/SigningTable.alternc-tmp && mv /etc/opendkim/SigningTable.alternc-tmp /etc/opendkim/SigningTable
  
  # Ask the dns server for restart
  $RNDC reconfig
  # Hook it !
  run-parts --arg=dns_reconfig --arg="$domain" /usr/lib/alternc/reload.d
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

    # Get the zone ttl
    local zonettl=$(dns_get_zonettl "$domain")

    # Generate the headers with the template
    local file=$(cat "$ZONE_TEMPLATE")

    # Add the entry
    file=$(
        echo -e "$file"
        $MYSQL_DO "select distinct replace(replace(dt.entry,'%TARGET%',sd.valeur), '%SUB%', if(length(sd.sub)>0,sd.sub,'@')) as entry from sub_domaines sd,domaines_type dt where sd.type=dt.name and sd.domaine='$domain' and sd.enable in ('ENABLE', 'ENABLED') order by entry ;"
    )

    ##### Mail autodetect for thunderbird / outlook - START
    # If $file contain DEFAULT_MX
    if [ ! -z "$(echo -e "$file" |egrep 'DEFAULT_MX' )" ] ; then 
      # If $file ! contain autoconfig -> add entry
      if [ -z "$(echo -e "$file" |egrep '^autoconfig' )" ] ; then 
        file="$(echo -e "$file" ; echo -e "autoconfig IN CNAME $FQDN.\n")"
      fi
      # if $file ! contain autodiscover -> add entry
      if [ -z "$(echo -e "$file" |egrep '^autodiscover' )" ] ; then 
        file="$(echo -e "$file" ; echo -e "autodiscover IN CNAME $FQDN.\n")"
      fi
    fi # End if containt DEFAULT_MX 
    ##### Mail autodetect for thunderbird / outlook - END

    ##### OpenDKIM signature management - START
    # If $file contain DEFAULT_MX
    if [ ! -z "$(echo -e "$file" |egrep 'DEFAULT_MX' )" ] ; then 
	# If necessary, we generate the key: 
	if [ ! -d "/etc/opendkim/keys/$domain" ] ; then
	    mkdir -p "/etc/opendkim/keys/$domain"

	    pushd "/etc/opendkim/keys/$domain" >/dev/null
	    opendkim-genkey -r -d "$domain" -s "alternc"
	    chown opendkim:opendkim alternc.private
	    popd

           local reg_domain=${domain/./\\.}

	    grep -q "^$reg_domain\$" /etc/opendkim/TrustedHosts || echo "$domain" >>/etc/opendkim/TrustedHosts
	    grep -q "^alternc\._domainkey\.$reg_domain " /etc/opendkim/KeyTable || echo "alternc._domainkey.$domain $domain:alternc:/etc/opendkim/keys/$domain/alternc.private" >> /etc/opendkim/KeyTable
	    grep -q "^$domain alternc\._domainkey\.$reg_domain\$" /etc/opendkim/SigningTable || echo "$domain alternc._domainkey.$domain" >> /etc/opendkim/SigningTable
	fi
	# we add alternc._domainkey with the proper key

        if [ -r "/etc/opendkim/keys/$domain/alternc.txt" ] ; then
	  file="$(echo -e "$file" ; cat "/etc/opendkim/keys/$domain/alternc.txt")"
        fi
    fi
    ##### OpenDKIM signature management - END

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
            s/@@PUBLIC_IP@@/$PUBLIC_IP/g;
            s/@@ZONETTL@@/$zonettl/g;
            " )
    
    # Add the manually entered resource records (after the special tag ;;; END ALTERNC AUTOGENERATE CONFIGURATION)
    if [ -r "$zone_file" ] ; then
        file=$(
            echo -e "$file"
            grep -A 10000 "$manual_tag" "$zone_file"
            )
    fi
    # Add the special tag at the end of the zone, if it is not here yet:
    if ! echo -e "$file" | grep -q "$manual_tag"
    then
	file=$(echo -e "$file"; echo "$manual_tag")
    fi

    # Init the file
    echo -e "$file" > "$zone_file"

    # And set his rights
    dns_chmod $domain
    # Add it to named conf
    dns_named_conf $domain

    # Hook it !
    run-parts --arg=dns_reload_zone --arg="$domain" /usr/lib/alternc/reload.d

    # ask bind to reload the zone
    $RNDC reload $domain
}

# some miscellaneous shell functions

print_domain_letter() {
    local domain="$1"

    local letter=`echo "$domain" | awk '{z=split($NF, a, ".") ; print substr(a[z-1], 1, 1)}'`
    if [ -z "$letter" ]; then
      letter="_"
    fi
    echo $letter
}

print_user_letter() {
    local user="$1"

    echo "$user" | awk '{print substr($1, 1, 1)}'
}

add_to_php_override() {
    local fqdn="$1"

    /usr/lib/alternc/basedir_prot.sh "$fqdn" >> "$DOMAIN_LOG_FILE"
}

remove_php_override() {
    local fqdn="$1"
    local letter=`print_domain_letter $fqdn`

    sed -i "/$fqdn/d" $APACHECONF_DIR/override_php.conf
    rm -f $APACHECONF_DIR/$letter/$fqdn
}

add_to_named_reload() {
    local domain="$1"
    local escaped_domain=`echo "$domain" | sed -e 's/\./\\\./g'`

    if [ "domain" = "all" ] || grep -q "^all$" "$RELOAD_ZONES_TMP_FILE"; then
        echo "all" > "$RELOAD_ZONES_TMP_FILE"
    else
        if ! grep -q "^${escaped_domain}$" "$RELOAD_ZONES_TMP_FILE"; then
            echo "$domain" >> "$RELOAD_ZONES_TMP_FILE"
        fi
    fi
}

# we assume that the serial line contains the "serial string", eg.:
#                 2005012703      ; serial
#
# returns 1 if file isn't readable
# returns 2 if we can't find the serial number
# returns 3 if a tempfile can't be created
increment_serial() {
    local domain="$1"
    local zone_file="$ZONES_DIR/$domain"
    local current_serial
    local new_serial
    local date
    local revision
    local today

    if [ ! -f "$zone_file" ]; then
        return 1
    fi

    # the assumption is here
    current_serial=`awk '/^..*serial/ {print $1}' < "$zone_file"` || return 2
    if [ -z "$current_serial" ]; then
        return 2
    fi

    date=`echo $current_serial | cut -c1-8`
    revision=`echo $current_serial | sed s/"${date}0\?"/""/g`
    today=`date +%Y%m%d`
    # increment the serial number only if the date hasn't changed
    if [ "$date" = "$today" ] ; then
        revision=$(($revision + 1))
    else
        revision=1
        date=$today
    fi
    new_serial="$date`printf '%.2d' $revision`"

    # replace serial number
    cp -a -f "$zone_file" "$zone_file.$$"
    awk -v "NEW_SERIAL=$new_serial" \
        '{if ($3 == "serial")
             print "		"NEW_SERIAL "	; serial"
          else
             print $0}' < "$zone_file" > "$zone_file.$$"
    mv -f "$zone_file.$$" "$zone_file"

    add_to_named_reload "$domain"

    return 0
}

change_host_ip() {
    local domain="$1"
    local zone_file="$ZONES_DIR/$domain"
    local ip="$2"
    local host="$3"
    local pattern
    local a_line

    if [ -z "$host" ]; then
        host="@"
    fi

    case "$host_type" in
        "$TYPE_IPV6")
            a_line="$host 	IN	AAAA 	$ip"
            pattern="^$host[[:space:]]*IN[[:space:]]*AAAA[[:space:]]+.+\$"
        ;;
        "$TYPE_CNAME")
            a_line="$host 	IN	CNAME 	$ip"
            pattern="^$host[[:space:]]*IN[[:space:]]*CNAME[[:space:]]+.+\$"
        ;;
        "$TYPE_TXT")
            a_line="$host 	IN	TXT 	$ip"
            pattern="^$host[[:space:]]*IN[[:space:]]*TXT[[:space:]]+.+\$"
        ;;
        *)
            a_line="$host 	IN	A 	$ip"
            pattern="^$host[[:space:]]*IN[[:space:]]*A[[:space:]]+.+\$"
    esac

    if [ ! -f "$zone_file" ]; then
        echo "Should change $host.$domain, but can't find $zone_file."
        return 1
    fi
    if grep -q "$pattern" "$zone_file"; then
        cp -a -f "$zone_file" "$zone_file.$$"
        sed "s/$pattern/$a_line/" < "$zone_file" > "$zone_file.$$"
        mv "$zone_file.$$" "$zone_file"
    else
        echo "$a_line" >> "$zone_file"
    fi
    add_to_named_reload "$domain"
}

add_host() {
    local domain="$1"
    local host_type="$2"
    local host="$3"
    local value="$4"
    local user="$5"
    local domain_letter=`print_domain_letter "$domain"`
    local user_letter=`print_user_letter "$user"`
    local ip
    local fqdn
    local vhost_directory

    delete_host "$domain" "$host" "$host_type"

    if [ "$host" = "@" -o -z "$host" ]; then
        FQDN="$domain"
    else
        FQDN="$host.$domain"
    fi

    case "$host_type" in
        "$TYPE_IP")
            ip="$value"
            ;;
        "$TYPE_IPV6")
            ip="$value"
            ;;
        "$TYPE_CNAME")
            ip="$value"
            ;;
        "$TYPE_TXT")
            ip="$value"
            ;;
        "$TYPE_WEBMAIL")
            ip="$PUBLIC_IP"
            add_to_php_override "$FQDN"
            ;;
        *)
            ip="$PUBLIC_IP"
            ;;
    esac

    if [ "$host" = "@" -o -z "$host" ]; then
        change_host_ip "$domain" "$ip" || true
        fqdn="$domain"
    else
        change_host_ip "$domain" "$ip" "$host" || true
        fqdn="${host}.${domain}"
    fi

    vhost_directory="${HTTP_DNS}/${domain_letter}/${fqdn}"
    htaccess_directory="${HTTP_DNS}/redir/${domain_letter}/${fqdn}"

    case "$host_type" in
      $TYPE_LOCAL)
		host_create_vhost $user $fqdn ${value}
        ;;

      $TYPE_WEBMAIL)
		host_create_webmail $user $fqdn
        ;;

      $TYPE_URL)
        mkdir -p "$htaccess_directory"
        # normalize the url provided to make sure the hostname part is
        # followed by at least /
        value=`echo $value | sed -e 's#\([^/:]*://\)\?\([^/]*\)/*\(.*\)#\1\2/\3#'`

		host_create_redirect $user $fqdn $value
        ;;

      $TYPE_IP)
        rm -f "$vhost_directory"
        rm -rf "$htaccess_directory/.htaccess"
        ;;

      *)
        echo "Unknow type code: $type" >> "$DOMAIN_LOG_FILE"
        ;;
    esac
 	host_enable_host $user $fqdn
}

delete_host() {
    local domain="$1"
    local host="$2"
    local host_type="$3"
    local domain_letter=`print_domain_letter "$domain"`
    local fqdn
    local escaped_host
    local escaped_fqdn
    local pattern

    if [ "$host" = "@" -o -z "$host" ]; then
        fqdn="$domain"
        escaped_host=""
    else
        fqdn="$host.$domain"
        escaped_host=`echo "$host" | sed 's/\([\*|\.]\)/\\\\\1/g'`
    fi

    if [ -f "$ZONES_DIR/$domain" ] ; then
        cp -a -f "$ZONES_DIR/$domain" "$ZONES_DIR/$domain.$$"

        case "$host_type" in
            "$TYPE_IPV6")
                pattern="/^$escaped_host[[:space:]]*IN[[:space:]]*AAAA[[:space:]]/d"
            ;;
            "$TYPE_CNAME")
                pattern="/^$escaped_host[[:space:]]*IN[[:space:]]*CNAME[[:space:]]/d"
            ;;
            "$TYPE_TXT")
                pattern="/^$escaped_host[[:space:]]*IN[[:space:]]*TXT[[:space:]]/d"
            ;;
            *)
                pattern="/^$escaped_host[[:space:]]*IN[[:space:]]*A[[:space:]]/d"
        esac

        sed -e "$pattern" < "$ZONES_DIR/$domain" > "$ZONES_DIR/$domain.$$"
        mv "$ZONES_DIR/$domain.$$" "$ZONES_DIR/$domain"
        increment_serial "$domain"
        add_to_named_reload "$domain"
    fi

    rm -f "$APACHECONF_DIR/$domain_letter/$fqdn"

    escaped_fqdn=`echo "$fqdn" | sed 's/\([\*|\.]\)/\\\\\1/g'`

    cp -a -f "$OVERRIDE_PHP_FILE" "$OVERRIDE_PHP_FILE.$$"
    sed -e "/\/${escaped_fqdn}\$/d" \
        < "$OVERRIDE_PHP_FILE" > "$OVERRIDE_PHP_FILE.$$"
    mv "$OVERRIDE_PHP_FILE.$$" "$OVERRIDE_PHP_FILE"

    rm -f "$HTTP_DNS/$domain_letter/$fqdn"
    rm -rf "$HTTP_DNS/redir/$domain_letter/$fqdn"
	host_disable_host $fqdn
}


init_zone() {
    local domain="$1"
    local escaped_domain=`echo "$domain" | sed -e 's/\./\\\./g'`
    local zone_file="$ZONES_DIR/$domain"
    local serial

    if [ ! -f "$zone_file" ]; then
        serial=`date +%Y%m%d`00
        sed -e "s/@@DOMAINE@@/$domain/g;s/@@SERIAL@@/$serial/g" \
            < "$ZONE_TEMPLATE" > "$zone_file"
        chgrp bind "$zone_file"
        chmod 640  "$zone_file"
    fi
    if ! grep -q "\"$escaped_domain\"" "$NAMED_CONF_FILE"; then
        cp -a -f "$NAMED_CONF_FILE" "$NAMED_CONF_FILE".prec
        sed -e "s/@@DOMAINE@@/$domain/g" \
                < "$NAMED_TEMPLATE" >> "$NAMED_CONF_FILE"
        add_to_named_reload "all"
    fi
}

remove_zone() {
    local domain="$1"
    local escaped_domain=`echo "$domain" | sed -e 's/\./\\\./g'`
    local zone_file="$ZONES_DIR/$domain"

    if [ -f "$zone_file" ]; then
        rm -f "$zone_file"
    fi

    if grep -q "\"$escaped_domain\"" "$NAMED_CONF_FILE"; then
        cp -a -f "$NAMED_CONF_FILE" "$NAMED_CONF_FILE.prec"
        cp -a -f "$NAMED_CONF_FILE" "$NAMED_CONF_FILE.$$"
        # That's for multi-line template
        #sed -e "/^zone \"$escaped_domain\"/,/^};/d" \
        # That's for one-line template
        grep -v "^zone \"$escaped_domain\"" \
            < "$NAMED_CONF_FILE" > "$NAMED_CONF_FILE.$$" || true
        mv -f "$NAMED_CONF_FILE.$$" "$NAMED_CONF_FILE"
        add_to_named_reload "all"
    fi
}

change_mx() {
    local domain="$1"
    local mx="$2"
    local zone_file="$ZONES_DIR/$domain"
    local pattern="^@*[[:space:]]*IN[[:space:]]*MX[[:space:]]*[[:digit:]]*[[:space:]].*\$"
    local mx_line="@ 	IN 	MX 	5 	$mx."

    # aller chercher le numéro de la ligne MX
    # XXX: comportement inconnu si plusieurs matchs ou MX commenté
    if grep -q "$pattern" "$zone_file"; then
        cp -a -f "$zone_file" "$zone_file.$$"
        sed -e "s/$pattern/$mx_line/" < "$zone_file" > "$zone_file.$$"
        mv "$zone_file.$$" "$zone_file"
    else
        echo "$mx_line" >> "$zone_file"
    fi

    increment_serial "$domain"
    add_to_named_reload "$domain"
}



# imprime le nom d'usager associé au domaine
get_account_by_domain() {
	# les admintools ne sont peut-être pas là
	if [ -x "/usr/bin/get_account_by_domain" ]
	then
		# only first field, only first line
		/usr/bin/get_account_by_domain "$1" | cut -d\  -f1 | cut -d'
' -f 1
	else
		# implantons localement ce que nous avons besoin, puisque admintools
		# n'est pas là
  		mysql --defaults-file=/etc/alternc/my.cnf -B -N -e \
  		'SELECT a.login FROM membres a, sub_domaines b WHERE a.uid = b.compte AND \
  		CONCAT(IF(sub="", "", CONCAT(sub, ".")), domaine) = "'"$1"'" LIMIT 1;'
	fi
}

# add the standard input to a given file, only if not already present
append_no_dupe() {
	realfile="$1"
	tmpfile=`mktemp`
	trap "rm -f $tmpfile; exit 1" 1 2 15
	cat > $tmpfile
	if [ -r "$realfile" ] &&
		(diff -q "$tmpfile" "$realfile" > /dev/null || \
			diff -u "$tmpfile" "$realfile"  | grep '^ ' | sed 's/^ //' | diff -q - "$tmpfile" > /dev/null)
	then
		ret=0
	else
		ret=1
		cat "$tmpfile" >> "$realfile"
	fi
	rm -f "$tmpfile"
	return "$ret"
}

add_dom_entry() {
	# protect ourselves from interrupts
	trap "rm -f ${override_f}.new; exit 1" 1 2 15
	# ajouter une entrée, seulement s'il n'y en pas déjà, pour ce domaine
	(echo "$1"; [ -r $override_f ] && cat $override_f) | \
	sort -u > ${override_f}.new && \
	cp ${override_f}.new ${override_f} && \
	rm ${override_f}.new
}

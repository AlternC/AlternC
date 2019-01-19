#!/bin/bash

# this script regenerate the SSL-* templates from the ORIGINAL non-ssl in parent folder
# launch it if you know that some templates has been changed in parent folder.

function convert {
    src=$1
    dst=$2
    (cat ../etc/alternc/templates/apache2/url.conf | sed -e 's#%%redirect%%#https://%%fqdn%%#' 
    cat "$src" |
    sed -e 's#:80#:443#' \
	-e "s#</VirtualHost>#  SSLEngine On\n  SSLCertificateFile %%CRT%%\n  SSLCertificateKeyFile %%KEY%%\n  %%CHAINLINE%%\n\n</VirtualHost>#i" \
    )	>"$dst"
}

# Those 3 are redirects from http://%%fqdn%% to https://%%fqdn%% PLUS the https://%%fqdn%% VHOST
convert "../roundcube/templates/apache2/roundcube.conf" "templates/roundcube-ssl.conf"
convert "../squirrelmail/templates/apache2/squirrelmail.conf" "templates/squirrelmail-ssl.conf"
convert "../etc/alternc/templates/apache2/panel.conf" "templates/panel-ssl.conf"
convert "../etc/alternc/templates/apache2/vhost.conf" "templates/vhost-ssl.conf"

# manual case : BOTH http and https are normal vhosts pointing to the same DocumentRoot
(cat ../etc/alternc/templates/apache2/vhost.conf 
 cat ../etc/alternc/templates/apache2/vhost.conf | 
    sed -e 's#:80#:443#' \
	-e "s#</VirtualHost>#  SSLEngine On\n  SSLCertificateFile %%CRT%%\n  SSLCertificateKeyFile %%KEY%%\n  %%CHAINLINE%%\n\n</VirtualHost>#i" 
) >templates/vhost-mixssl.conf




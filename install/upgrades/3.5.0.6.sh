#!/bin/bash

#Remove duplicate file and maitain only conf-available/alternc-ssl.conf file
rm -f /etc/alternc/apache2-ssl.conf
rm -f /etc/alternc/templates/apache2/mods-available/alternc-ssl.conf
rm -f /etc/apache2/mods-available/alternc-ssl.conf

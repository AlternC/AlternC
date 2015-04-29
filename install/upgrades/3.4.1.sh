#!/bin/sh

echo "Fixing ;;; END ALTERNC AUTOGENERATE CONFIGURATION missing some space..."

sed -i -e 's/;;;END ALTERNC/;;; END ALTERNC/' /var/lib/alternc/bind/zones/*


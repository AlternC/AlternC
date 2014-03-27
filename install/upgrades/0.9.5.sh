#!/bin/bash

set -e

chmod 640 /etc/alternc/local.sh
chown root:postfix /etc/postfix/my*
chmod 640 /etc/postfix/my*
rm -f /var/alternc/bureau/admin/adm_list2.php

#!/bin/sh

# change ownership of the panel's file, MUST be root:root 
# since some cron-script of AlternC are launched as root.

chown -R root:root /usr/share/alternc/panel 

# AlternC's backup of system files must NOT be readable but everyone !
chmod -R og-rwx /var/lib/alternc/backups


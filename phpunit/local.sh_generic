#!/bin/bash
#
# AlternC - Web Hosting System - Configuration
# This file will be modified on package configuration
# (e.g. upgrade or dpkg-reconfigure alternc)

# Hosting service name
HOSTING="AlternC"

# Primary hostname for this box (will be used to access the management panel)
FQDN="phpunit-test.tld"

# Public IP
PUBLIC_IP="128.66.0.42"

# Internal IP
# (most of the time, should be equal to PUBLIC_IP, unless you are behind
# firewall doing address translation)
INTERNAL_IP="128.66.0.99"

# Monitoring IP or network (will be allowed to access Apache status)
MONITOR_IP=""

# Primary DNS hostname
NS1_HOSTNAME="phpunit-test.tld"

# Secondary DNS hostname
NS2_HOSTNAME="phpunit-test.tld"

# Mail server hostname
DEFAULT_MX="phpunit-test.tld"

# Secondary mail server hostname
DEFAULT_SECONDARY_MX=""

# Note: MySQL username/password configuration now stored in /etc/alternc/my.cnf

# quels clients mysql sont permis (%, localhost, etc)
MYSQL_CLIENT="localhost"

# the type of backup created by the sql backup script
# valid options are "rotate" (newsyslog-style) or "date" (suffix is the date)
SQLBACKUP_TYPE="rotate"

# overwrite existing files when backing up
SQLBACKUP_OVERWRITE="no"

# known slave servers, empty for none, localhost is special (no ssh)
ALTERNC_SLAVES=""

# File to look at for forced launch of update_domain (use incron)
INOTIFY_UPDATE_DOMAIN="/tmp/inotify_update_domain.lock"

INOTIFY_DO_ACTION="/tmp/inotify_do_action.lock"

# Folder holding data (used for quota management)
ALTERNC_HTML="/tmp/var/www/alternc"
ALTERNC_MAIL="/tmp/var/mail/alternc"
ALTERNC_LOGS="/tmp/var/logs/alternc/sites"


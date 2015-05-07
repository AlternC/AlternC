
@reboot	  root	      mkdir -p /var/run/alternc-ssl && chown alterncpanel:alterncpanel /var/run/alternc-ssl 

# in case incron didn't work, fix ssl aliases every hour:
44 * * * * root  /usr/lib/alternc/ssl_alias_manager.sh 

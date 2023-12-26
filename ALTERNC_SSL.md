# Removing AlternC SSL

*Before* version 3.[1,2,3].12, the sub-domain types *-ssl and *-mixssl are
removed, and any sub-domains using them marked for deletion when this
package is removed. If the package is purge, the database tables provided
by this package are also removed from the AlternC database:
  * certificates
  * certif_alias
  * certif_host

This causes data loss when upgrading to AlternC 3.5 for those who are using
alternc-ssl.

With *3.[1,2,3].12* onward, no changes are made to the sub domains, domain
types, or database tables. The upgrade scripts to AlternC 3.5 should handle
the data migration.

If you are using 3.[1,2,3].12 or later and are looking to remove the
configuration and data without passing to AlternC 3.5 or later the following
clean-up steps should be performed manually:

1. Remove the following domain types from the domaines_type table:
  * vhost-ssl
  * vhost-mixssl
  * roundcube-ssl
  * squirrelmail-ssl
  * panel-ssl
  * php52-ssl
  * php42-mixssl
2. Mark all sub-domains using the above types for deletion by modifying
the web_action column in the database, or deleting them through the web
interface
3. Remove the template files from /etc/alternc/templates/apache2 for the
above domain types.
4. Rebuild the web configuration by running update_domains
5. Drop the following tables:
  * certificates
  * certif_alias
  * certif_host

For more information about the issues

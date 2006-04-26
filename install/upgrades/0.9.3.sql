-- add support for .it
INSERT IGNORE INTO tld VALUES ('it', 1);
INSERT IGNORE INTO tld VALUES ('ws', 1);

-- remove the old "estelle" default mx in older tables
ALTER TABLE `domaines` MODIFY `mx` varchar(64) DEFAULT NULL;
ALTER TABLE `domaines_standby` MODIFY `mx` varchar(64) DEFAULT NULL;

-- add the new variable table
--
-- if comment is null, then the variable is internal and will not show
-- up in the generic configuration panel
CREATE TABLE IF NOT EXISTS variable (
  name varchar(48) NOT NULL default '',
  value longtext NOT NULL,
  comment mediumtext NULL,
  PRIMARY KEY  (name),
  KEY name (name)
) TYPE=MyISAM;

-- hosting_tld: only used, for now, in bureau/admin/adm_*add.php
INSERT IGNORE INTO `variable` (name, value, comment) VALUES ('hosting_tld', 0,
'This is a FQDN that designates the main hostname of the service.

For example, hosting_tld determines in what TLD the "free" user domain
is created. If this is set to "example.com", a checkbox will appear in
the user creation dialog requesting the creator if he wants to create
the domain "username.example.com".

If this is set to 0 or a "false" string, it will be ignored.');

-- Adding the sasl field that will receive the cleartext password for SASL smtp auth.
ALTER TABLE `mail_users` ADD `sasl` VARCHAR( 255 ) NOT NULL ;

-- As of Mysql-server 4.0 on sarge, we should grant any right to the debian sys maint : 
GRANT ALL PRIVILEGES ON *.* TO 'debian-sys-maint' WITH GRANT OPTION; 

-- USE mysql;

-- In AlternC 0.9.3, the GRANTS were created with the wrong Db
-- pattern: the underscores were not escaped.

-- this allowed the user to create extra tables not under alternc's
-- quota controls since the underscore is a wildcard in MySQL.

-- the database creation and deletion code has been update, so the
-- grants themselves need to be modified otherwise the AlternC
-- deletion code will fail and produce evil errors
-- UPDATE `db` set `Db` = REPLACE(`Db`,'_','\_') WHERE `Db` REGEXP '[^\\]_';


-- make sure this has an effect at all.
-- FLUSH PRIVILEGES;

-- New table for the MySQL servers
CREATE TABLE IF NOT EXISTS `db_servers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `host` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `client` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM COMMENT='List of the databases servers';

-- Alter table membres to add 
ALTER TABLE `membres` ADD db_server_id int(10) DEFAULT NULL;

-- Alter table FTP to add 'enabled' 
ALTER TABLE `ftpusers` ADD `enabled` boolean NOT NULL DEFAULT TRUE ;

-- New table for VM requests
CREATE TABLE IF NOT EXISTS `vm_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(256) NOT NULL,
  `date_start` datetime NOT NULL, 
  `date_end` datetime DEFAULT NULL,
  `uid` int(10) unsigned NOT NULL,
  `serialized_object` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_end` (`date_end`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM COMMENT='VM Allocation requests';


CREATE TABLE IF NOT EXISTS `actions` (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 type enum ('CREATE_FILE','CREATE_DIR','DELETE','MOVE','FIXDIR','FIXFILE', 'FIXUSER'),
 parameters longtext default NULL,
 creation timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 begin timestamp,
 end timestamp,
 user varchar(255) default NULL,
 status int(8) unsigned default NULL,
 PRIMARY KEY ( `id` )
) ENGINE=MyISAM COMMENT = 'generic actions';

-- Alter table domaines to add zone ttl field
ALTER TABLE `domaines` ADD zonettl int(10) unsigned NOT NULL default '86400';

-- Alter table sub_domaines pour contenir au nouveau schema
alter table sub_domaines drop primary key;
alter table sub_domaines add UNIQUE (compte,domaine,sub,type,valeur);
alter table sub_domaines add id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;


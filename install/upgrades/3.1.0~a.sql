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

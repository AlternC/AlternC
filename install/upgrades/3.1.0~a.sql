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


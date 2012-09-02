
CREATE TABLE IF NOT EXISTS `upnp` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `class` varchar(64) NOT NULL,
  `protocol` varchar(3) NOT NULL,
  `port` int(10) unsigned NOT NULL,
  `mandatory` tinyint(3) unsigned NOT NULL,
  `enabled` tinyint(3) unsigned NOT NULL,
  `lastcheck` datetime NOT NULL,
  `lastupdate` datetime NOT NULL,
  `result` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM COMMENT='UPnP port forwards and their status.';



CREATE TABLE `certificates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `status` tinyint(3) unsigned NOT NULL,
  `shared` tinyint(3) unsigned NOT NULL,
  `fqdn` varchar(255) NOT NULL,
  `altnames` text NOT NULL,
  `validstart` datetime NOT NULL,
  `validend` datetime NOT NULL,
  `sslcsr` text NOT NULL,
  `sslkey` text NOT NULL,
  `sslcrt` text NOT NULL,
  `sslchain` text NOT NULL,
  `ssl_action` varchar(32) NOT NULL,
  `ssl_result` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `ssl_action` (`ssl_action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `certif_alias` (
  `name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Global aliases defined for SSL certificates FILE validation processes';

CREATE TABLE IF NOT EXISTS `certif_hosts` (
  `certif` int(10) unsigned NOT NULL,
  `sub` int(10) unsigned NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`certif`,`sub`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='VHosts of a user using defined or self-signed certificates';

INSERT IGNORE INTO defquotas VALUES ('ssl', 0, 'default');

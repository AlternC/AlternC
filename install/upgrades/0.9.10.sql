ALTER IGNORE TABLE `membres` ADD COLUMN `notes` TEXT NOT NULL AFTER `type`;

CREATE TABLE IF NOT EXISTS `policy` (
  `name` varchar(64) NOT NULL,
  `minsize` tinyint(3) unsigned NOT NULL,
  `maxsize` tinyint(3) unsigned NOT NULL,
  `classcount` tinyint(3) unsigned NOT NULL,
  `allowlogin` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='The password policies for services';


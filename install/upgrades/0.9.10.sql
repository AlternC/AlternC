ALTER IGNORE TABLE `membres` ADD COLUMN `notes` TEXT NOT NULL AFTER `type`;

CREATE TABLE IF NOT EXISTS `policy` (
  `name` varchar(64) NOT NULL,
  `minsize` tinyint(3) unsigned NOT NULL,
  `maxsize` tinyint(3) unsigned NOT NULL,
  `classcount` tinyint(3) unsigned NOT NULL,
  `allowlogin` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='The password policies for services';


INSERT IGNORE INTO `variable` (`name` ,`value` ,`comment`)
VALUES (
'subadmin_restriction', '', 
'This variable set the way the account list works for accounts other than "admin" (2000). 0 (default) = admin other than admin/2000 can see their own account, but not the other one 1 = admin other than admin/2000 can see any account by clicking the ''show all accounts'' link. '
);

-- --------------------------------------------------------
-- TABLES de mémorisation de la taille des dossiers db/listes

CREATE TABLE IF NOT EXISTS `size_db` (
  `db` varchar(255) NOT NULL default '',
  `size` int(10) unsigned NOT NULL default '0',
  `ts` timestamp(14) NOT NULL,
  PRIMARY KEY  (`db`),
  KEY `ts` (`ts`)
) TYPE=MyISAM COMMENT='MySQL Database used space';


CREATE TABLE IF NOT EXISTS `size_mailman` (
  `list` varchar(255) NOT NULL default '',
  `uid` int(11) NOT NULL default '0',
  `size` int(10) unsigned NOT NULL default '0',
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`list`),
  KEY `ts` (`ts`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM COMMENT='Mailman Lists used space';

-- IPv6 compatibility :  
ALTER TABLE `slaveip` CHANGE `ip` `ip` VARCHAR(40);
ALTER TABLE `sessions` CHANGE `ip` `ip` VARCHAR( 40 ) NULL;



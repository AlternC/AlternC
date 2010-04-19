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


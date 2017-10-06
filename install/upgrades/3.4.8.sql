CREATE TABLE IF NOT EXISTS `dovecot_quota` (
  `user` varchar(320) NOT NULL,
  `quota_dovecot` bigint(20) NOT NULL DEFAULT '0',
  `nb_messages` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `piwik_users` ADD `passwd` VARCHAR(255) NOT NULL AFTER `login`;

DELETE FROM `default_subdomains` WHERE  `domain_type` = 'ROUNDCUBE' AND `sub`='mail';
DELETE FROM `default_subdomains` WHERE  `domain_type` = 'URL' AND `sub`='mail';

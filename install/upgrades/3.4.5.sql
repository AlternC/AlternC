

CREATE TABLE IF NOT EXISTS `csrf` (
  `cookie` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `token` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created` datetime NOT NULL,
  `used` tinyint(3) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='csrf tokens for AlternC forms';

ALTER TABLE `csrf` ADD PRIMARY KEY (`session`,`token`), ADD KEY `created` (`created`);

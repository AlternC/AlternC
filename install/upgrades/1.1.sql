-- Add function who are not in mysql 5 to be able ton convert ipv6 to decimal (and reverse it)
DELIMITER //
CREATE FUNCTION INET_ATON6(n CHAR(39))
RETURNS DECIMAL(39) UNSIGNED
DETERMINISTIC
BEGIN
    RETURN CAST(CONV(SUBSTRING(n FROM  1 FOR 4), 16, 10) AS DECIMAL(39))
                       * 5192296858534827628530496329220096 -- 65536 ^ 7
         + CAST(CONV(SUBSTRING(n FROM  6 FOR 4), 16, 10) AS DECIMAL(39))
                       *      79228162514264337593543950336 -- 65536 ^ 6
         + CAST(CONV(SUBSTRING(n FROM 11 FOR 4), 16, 10) AS DECIMAL(39))
                       *          1208925819614629174706176 -- 65536 ^ 5
         + CAST(CONV(SUBSTRING(n FROM 16 FOR 4), 16, 10) AS DECIMAL(39)) 
                       *               18446744073709551616 -- 65536 ^ 4
         + CAST(CONV(SUBSTRING(n FROM 21 FOR 4), 16, 10) AS DECIMAL(39))
                       *                    281474976710656 -- 65536 ^ 3
         + CAST(CONV(SUBSTRING(n FROM 26 FOR 4), 16, 10) AS DECIMAL(39))
                       *                         4294967296 -- 65536 ^ 2
         + CAST(CONV(SUBSTRING(n FROM 31 FOR 4), 16, 10) AS DECIMAL(39))
                       *                              65536 -- 65536 ^ 1
         + CAST(CONV(SUBSTRING(n FROM 36 FOR 4), 16, 10) AS DECIMAL(39))
         ;
END;
//
DELIMITER ;
DELIMITER //
CREATE FUNCTION INET_NTOA6(n DECIMAL(39) UNSIGNED)
RETURNS CHAR(39)
DETERMINISTIC
BEGIN
  DECLARE a CHAR(39)             DEFAULT '';
  DECLARE i INT                  DEFAULT 7;
  DECLARE q DECIMAL(39) UNSIGNED DEFAULT 0;
  DECLARE r INT                  DEFAULT 0;
  WHILE i DO
    -- DIV doesn't work with nubers > bigint
    SET q := FLOOR(n / 65536);
    SET r := n MOD 65536;
    SET n := q;
    SET a := CONCAT_WS(':', LPAD(CONV(r, 10, 16), 4, '0'), a);

    SET i := i - 1;
  END WHILE;

  SET a := TRIM(TRAILING ':' FROM CONCAT_WS(':',
                                            LPAD(CONV(n, 10, 16), 4, '0'),
                                            a));

  RETURN a;

END;
//
DELIMITER ;

-- New table for the authorised IP
CREATE TABLE IF NOT EXISTS `authorised_ip` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(11) unsigned NOT NULL default '0',
  `ip` varchar(40) not null,
  `subnet` integer(3) not null default 32,
  `infos` varchar(255) not null default '',
  PRIMARY KEY  (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM COMMENT='Table with list of authorised ip and subnet';

-- Who have authorised IP ?
CREATE TABLE IF NOT EXISTS `authorised_ip_affected` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `authorised_ip_id` int(10) unsigned not null,
  `protocol` varchar(15) not null,
  `parameters` varchar(30) default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM COMMENT='Table with list of protocol <-> authorised ip and subnet';

INSERT IGNORE INTO `variable` (`name` ,`value` ,`comment`)
VALUES (
'auth_ip_ftp_default_yes', '1',
'This variable set if you want to allow all IP address to access FTP by default. If the user start to define some IP or subnet in the allow list, only those he defined will be allowed. This variable can take two value : 0 or 1.'
);

--
-- Main address table.
--
-- Addresses for domain.

CREATE TABLE `address` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, -- Technical id.
  `domain_id` bigint(20) unsigned DEFAULT NULL REFERENCES `sub_domain`(`id`), -- FK to sub_domains.
  `address` varchar(255) NOT NULL, -- The address.
  `password` varchar(255) DEFAULT NULL, -- The password associated to the address.
  `enabled` int(1) unsigned NOT NULL DEFAULT '1', -- Enabled flag.
  `expire_date` datetime DEFAULT NULL, -- Expiration date, used for temporary addresses.
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Update date, for technical usage only.
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain_id` (`domain_id`,`address`)
) COMMENT = 'This is the main address table. It represents an address as in RFC2822';


--
-- Mailbox table.
-- 
-- Local delivered mailboxes.

CREATE TABLE `mailbox` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, -- Technical id.
  `address_id` bigint(20) unsigned NOT NULL REFERENCES `address`(`id`), -- Reference to address.
  `path` varchar(255) NOT NULL, -- Relative path to the mailbox.
  `quota` bigint(20) unsigned DEFAULT NULL, -- Quota for this mailbox.
  `delivery` varchar(255) NOT NULL, -- Delivery transport.
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Update date, for technical usage only.
  PRIMARY KEY (`id`),
  UNIQUE KEY `address_id` (`address_id`)
) COMMENT = 'Table containing local deliverd mailboxes.';

--
-- Other recipients.
--
-- Other recipients for an address (aliases)

CREATE TABLE `recipient` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, -- Technical id.
  `address_id` bigint(20) unsigned NOT NULL REFERENCES `address`(`id`), -- Reference to address
  `recipients` text NOT NULL, -- Recipients
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Update date, for technical usage only.
  PRIMARY KEY (`id`),
  UNIQUE KEY `address_id` (`address_id`)
) COMMENT = 'Table containing other recipients (aliases) for an address.';

--
-- Mailman table.
--
-- Table containing mailman addresses

CREATE TABLE `mailman` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, -- Technical id.
  `address_id` bigint(20) unsigned NOT NULL REFERENCES `address`(`id`), -- Reference to address
  `delivery` varchar(255) NOT NULL, -- Delivery transport.
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Update date, for technical usage only.
  PRIMARY KEY (`id`),
  UNIQUE KEY `address_id` (`address_id`)
) COMMENT = 'Table containing mailman list addresses.';


-- Regenerate apache conf to enable mpm-itk
update sub_domaines set web_action = 'UPDATE';
update domaines     set dns_action = 'UPDATE';

--
-- Scheduled tasks
--
CREATE TABLE IF NOT EXISTS `cron` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `url` varchar(2100) NOT NULL,
  `user` varchar(64) NOT NULL,
  `password` varchar(64) NOT NULL,
  `schedule` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `next_execution` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

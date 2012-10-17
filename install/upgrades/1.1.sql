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

-- Main address table.
--
-- Addresses for domain.

CREATE TABLE IF NOT EXISTS `address` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, -- Technical id.
  `domain_id` bigint(20) unsigned NOT NULL REFERENCES `domaines`(`id`), -- FK to sub_domains.
  `address` varchar(255) NOT NULL, -- The address.
  `type` char(8) NOT NULL, -- standard emails are '', other may be 'mailman' or 'sympa' ...
  `password` varchar(255) DEFAULT NULL, -- The password associated to the address.
  `enabled` int(1) unsigned NOT NULL DEFAULT '1', -- Enabled flag.
  `expire_date` datetime DEFAULT NULL, -- Expiration date, used for temporary addresses.
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Update date, for technical usage only.
  `mail_action` enum('OK','DELETE','DELETING') NOT NULL default 'OK', -- mail_action is DELETE or DELETING when deleting a mailbox by cron
  PRIMARY KEY (`id`),
  UNIQUE INDEX `fk_domain_id` (`domain_id`,`address`)
) COMMENT = 'This is the main address table. It represents an address as in RFC2822';

--
-- Mailbox table.
--
-- Local delivered mailboxes.

CREATE TABLE IF NOT EXISTS `mailbox` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, -- Technical id.
  `address_id` bigint(20) unsigned NOT NULL REFERENCES `address`(`id`), -- Reference to address.
  `path` varchar(255) NOT NULL, -- Relative path to the mailbox.
  `quota` bigint(20) unsigned DEFAULT NULL, -- Quota for this mailbox.
  `delivery` varchar(255) NOT NULL, -- Delivery transport.
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Update date, for technical usage only.
  `bytes` bigint(20) NOT NULL DEFAULT '0', -- number of bytes in the mailbox, filled by dovecot
  `messages` int(11) NOT NULL DEFAULT '0', -- number of messages in the mailbox, filled by dovecot
  `lastlogin` datetime NOT NULL, -- Last login, filled by dovecot
  `mail_action` enum('OK','DELETE','DELETING') NOT NULL default 'OK', -- mail_action is DELETE or DELETING when deleting a mailbox by cron
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



--
-- Structure de la table `piwik_users`
--

CREATE TABLE IF NOT EXISTS `piwik_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user` (`login`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=18 ;

--
-- Structure de la table `piwik_sites`
--

CREATE TABLE IF NOT EXISTS `piwik_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `piwik_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_site_per_user` (`uid`,`piwik_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


-- No longer MySQL User quotas
DELETE FROM defquotas WHERE quota = 'mysql_users';
DELETE FROM quotas WHERE name = 'mysql_users';

-- Raw web statistics are deprecated since vlogger
DELETE FROM quotas WHERE name = 'sta2';
DELETE FROM defquotas WHERE quota = 'sta2';
DROP TABLE stats2;

-- With Dovecot, no more use of size_mail
DROP TABLE size_mail;

-- now that we have separate packages for the webmails, we can't serve webmail domainetype anymore
DELETE FROM domaines_type WHERE name='webmail';
UPDATE domaines_type SET compatibility=REPLACE(compatibility,'webmail,','');

-- Edit domains_type
ALTER TABLE `domaines_type` ADD create_tmpdir BOOLEAN NOT NULL DEFAULT FALSE ;
ALTER TABLE `domaines_type` ADD create_targetdir BOOLEAN NOT NULL DEFAULT FALSE ;
UPDATE domaines_type SET create_tmpdir=true, create_targetdir=true WHERE target='DIRECTORY';

-- Defaults subdomains to create when a domain is added
CREATE TABLE IF NOT EXISTS `default_subdomains` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sub` varchar(255) NOT NULL,
  `domain_type` varchar(255) NOT NULL,
  `domain_type_parameter` varchar(255) NOT NULL,
  `concerned` enum('BOTH','MAIN','SLAVE') NOT NULL DEFAULT 'MAIN',
  `enabled` boolean not null default true,
  PRIMARY KEY  (`id`)
) COMMENT='Contains the defaults subdomains created on domains creation';

INSERT INTO `default_subdomains` (`sub`, `domain_type`, `domain_type_parameter`, `concerned`) VALUES
('www',  'VHOST',   '%%DOMAINDIR%%',             'MAIN'),
('mail', 'WEBMAIL', '',                          'MAIN'),
('',     'URL',     'http://www.%%DOMAIN%%',     'MAIN'),
('www',  'URL',     'http://www.%%TARGETDOM%%',  'SLAVE'),
('mail', 'URL',     'http://mail.%%TARGETDOM%%', 'SLAVE'),
('',     'URL',     'http://%%TARGETDOM%%',      'SLAVE');


-- -------------------------------------------------
-- Update domaines table.
alter table domaines
    drop primary key;

alter table domaines
    add column id bigint(20) unsigned NOT NULL AUTO_INCREMENT primary key;

alter table domaines
    add unique key(domaine);
-- End of update domaines table.
-- -------------------------------------------------

-- -------------------------------------------------
-- Add column to follow the migration process and detect unmigrated entries
alter table mail_domain
    add column migration_state enum ('UNMIGRATED', 'WONT BE MIGRATED', 'DB MIGRATED', 'FS MIGRATED') not null default 'UNMIGRATED';

alter table mail_users
    add column migration_state enum ('UNMIGRATED', 'WONT BE MIGRATED', 'DB MIGRATED', 'FS MIGRATED') not null default 'UNMIGRATED';

alter table mail_alias
    add column migration_state enum ('UNMIGRATED', 'WONT BE MIGRATED', 'DB MIGRATED', 'FS MIGRATED') not null default 'UNMIGRATED';


-- Tag mail_domain addresses without @ to not migrated.
update mail_domain
    set mail_domain.migration_state='WONT BE MIGRATED'
    where locate('@', mail_domain.mail) = 0;

-- Tag mail_domain addresses with no corresponding domain in domaines to not migrate
update mail_domain
    set mail_domain.migration_state='WONT BE MIGRATED'
    where locate('@', mail_domain.mail) > 0
        and substring_index(mail_domain.mail, '@', -1) not in (select domaines.domaine from domaines);

-- Tag mail_domain addresses with two @
update mail_domain
    set mail_domain.migration_state='WONT BE MIGRATED'
    where locate('@', mail_domain.mail) > 0
        and mail_domain.mail like '%@%@%';

-- Tag mail
update mail_users
    set mail_users.migration_state='WONT BE MIGRATED'
    where alias in (select replace(mail_domain.mail, '@', '_')
        from mail_domain
        where mail_domain.migration_state='WONT BE MIGRATED');

update mail_users
    set mail_users.migration_state='WONT BE MIGRATED'
    where alias in (select mail_domain.mail
        from mail_domain
        where mail_domain.migration_state='WONT BE MIGRATED');


-- update mail_domain, domaines
--     set migration_state='WONT BE MIGRATED'
--     where mail = domaines.domaine;

-- End of add column to follow the migration process and detect unmigrated entries
-- -------------------------------------------------

-- -------------------------------------------------
-- Insert data into new model from old model.

-- Insert local managed mailboxes
insert into address (domain_id, address, type, password)
    select domaines.id as domain_id,
    substring_index(mail_domain.mail, '@', 1) as address,
    '' as type,
    mail_users.password
    from domaines
        join mail_domain
            on domaines.domaine = substring_index(mail_domain.mail, '@', -1)
        join mail_users
            on locate(concat(trim(mail_users.alias), ' '), concat(trim(replace(mail_domain.alias, '\n', ' ')), ' ')) = 1
    where mail_domain.migration_state='UNMIGRATED'
        and mail_domain.mail like '%@%'
        and mail_domain.type = 0
        and mail_domain.pop = 1;

insert into mailbox (address_id, path, delivery, lastlogin)
    select address.id,
    mail_users.path,
    'dovecot' as delivery,
    0 as last_login
    from address
        join domaines
            on domaines.id = address.domain_id
        join mail_domain
            on mail_domain.mail = concat(address.address, '@', domaines.domaine)
        join mail_users
            on locate(concat(trim(mail_users.alias), ' '), concat(trim(replace(mail_domain.alias, '\n', ' ')), ' ')) = 1
    where mail_domain.migration_state='UNMIGRATED'
        and mail_domain.mail like '%@%'
        and mail_domain.type = 0
        and mail_domain.pop = 1;

insert into recipient (address_id, recipients)
    select address.id as address_id,
    recipients.recipients
    from address
        join domaines
            on domaines.id = address.domain_id
        join (select
                mail_domain.mail as mail,
                trim(both '\n' from trim(replace(mail_domain.alias, replace(mail_domain.mail, '@', '_'), ''))) as recipients
            from mail_domain
            where mail_domain.migration_state='UNMIGRATED'
                and mail_domain.mail like '%@%'
                and mail_domain.type = 0
                and mail_domain.pop = 1) as recipients
            on recipients.recipients != ''
                and recipients.mail = concat(address.address, '@', domaines.domaine);

update mail_domain, domaines, mail_users
    set mail_domain.migration_state = 'DB MIGRATED',
        mail_users.migration_state = 'DB MIGRATED'
    where mail_domain.migration_state='UNMIGRATED'
        and domaines.domaine = substring_index(mail_domain.mail, '@', -1)
        and locate(concat(trim(mail_users.alias), ' '), concat(trim(replace(mail_domain.alias, '\n', ' ')), ' ')) = 1
        and mail_domain.mail like '%@%'
        and mail_domain.type = 0
        and mail_domain.pop = 1;

update mail_domain, domaines, mail_users
    set mail_users.migration_state = 'DB MIGRATED'
    where mail_domain.migration_state='UNMIGRATED'
        and domaines.domaine = substring_index(mail_domain.mail, '@', -1)
        and mail_users.alias = mail_domain.mail
        and mail_domain.mail like '%@%'
        and mail_domain.type = 0
        and mail_domain.pop = 1;

-- Here we should have inserted all local managed mailboxes and their aliases.

-- Insert alias only adresses.
insert into address (domain_id, address, type, password)
    select domaines.id as domain_id,
    substring_index(mail_domain.mail, '@', 1) as address,
    '' as type,
    '' as password
    from domaines
        join mail_domain
            on domaines.domaine = substring_index(mail_domain.mail, '@', -1)
    where mail_domain.mail like '%@%'
        and mail_domain.type = 0
        and mail_domain.pop = 0;

insert into recipient (address_id, recipients)
    select address.id as address_id,
    trim(both '\n' from recipients.recipients)
    from address
        join domaines
            on domaines.id = address.domain_id
        join (select
                mail_domain.mail as mail,
                mail_domain.alias as recipients
            from mail_domain
            where mail_domain.migration_state='UNMIGRATED'
                and mail_domain.mail like '%@%'
                and mail_domain.type = 0
                and mail_domain.pop = 0) as recipients
            on recipients.recipients != ''
                and recipients.mail = concat(address.address, '@', domaines.domaine);

update mail_domain, domaines
    set mail_domain.migration_state = 'DB MIGRATED'
    where mail_domain.migration_state='UNMIGRATED'
        and domaines.domaine = substring_index(mail_domain.mail, '@', -1)
        and mail_domain.mail like '%@%'
        and mail_domain.type = 0
        and mail_domain.pop = 0;

-- Insert procmail managed addresses.
-- Note: those addresses should be treated as local managed adresses.

-- Insert mailman list addresses
insert into address (domain_id, address, type, password)
    select domaines.id as domain_id,
    substring_index(mail_domain.mail, '@', 1) as address,
    'mailman' as type,
    '' as password
    from domaines
        join mail_domain
            on domaines.domaine = substring_index(mail_domain.mail, '@', -1)
        join mail_alias
            on mail_alias.mail = mail_domain.alias
    where mail_domain.migration_state='UNMIGRATED'
        and mail_domain.type = 1
        and mail_domain.pop = 0
        and mail_alias.alias like '"| /var/lib/mailman/mail/mailman %';

update mail_domain, domaines, mail_alias
    set mail_domain.migration_state = 'DB MIGRATED'
    where mail_domain.migration_state='UNMIGRATED'
        and domaines.domaine = substring_index(mail_domain.mail, '@', -1)
        and mail_alias.mail = mail_domain.alias
        and mail_domain.type = 1
        and mail_alias.alias like '"| /var/lib/mailman/mail/mailman %';

-- Insert sympa list addresses
insert into address (domain_id, address, type, password)
    select domaines.id as domain_id,
    substring_index(mail_domain.mail, '@', 1) as address,
    'sympa' as type,
    '' as password
    from domaines
        join mail_domain
            on domaines.domaine = substring_index(mail_domain.mail, '@', -1)
        join mail_alias
            on mail_alias.mail = mail_domain.alias
    where mail_domain.migration_state='UNMIGRATED'
        and mail_domain.type = 1
        and mail_domain.pop = 0
        and mail_alias.alias like '"|/usr/lib/sympa/bin/bouncequeue %';

update mail_domain, domaines, mail_alias
    set mail_domain.migration_state = 'DB MIGRATED'
    where mail_domain.migration_state='UNMIGRATED'
        and domaines.domaine = substring_index(mail_domain.mail, '@', -1)
        and mail_alias.mail = mail_domain.alias
        and mail_domain.type = 1
        and mail_alias.alias like '"|/usr/lib/sympa/bin/bouncequeue %';
-- End of insert data into new model from old model.
-- -------------------------------------------------

--
-- Structure de la vue `dovecot_view`
--
CREATE OR REPLACE VIEW `dovecot_view` AS
SELECT concat(`address`.`address`,'@',`domaines`.`domaine`) AS `user`,
concat('*:storage=',cast(`mailbox`.`quota` as char charset latin1),'M') AS `userdb_quota_rule`,
`address`.`password` AS `password`,
`mailbox`.`path` AS `userdb_home`,
`domaines`.`compte` AS `userdb_uid`,
`domaines`.`compte` AS `userdb_gid`,
`mailbox`.`bytes` AS `quota_dovecot`,
`mailbox`.`messages` AS `nb_messages`
from ((`mailbox`
join `address` on((`address`.`id` = `mailbox`.`address_id`)))
join `domaines` on((`domaines`.`id` = `address`.`domain_id`)))
where `address`.`enabled` = 1
;

--
-- Structure de la vue `alias_view`
--

CREATE OR REPLACE VIEW `alias_view` AS
select concat(`address`.`address`,'@',`domaines`.`domaine`) AS `mail`,
concat(if(isnull(`mailbox`.`id`),'',concat(concat(`address`.`address`,'@',`domaines`.`domaine`),'\n')),`recipient`.`recipients`) AS `alias`
from (((`recipient` join `address` on((`address`.`id` = `recipient`.`address_id`)))
left join `mailbox` on((`mailbox`.`address_id` = `address`.`id`)))
join `domaines` on((`domaines`.`id` = `address`.`domain_id`)))
where `address`.`enabled` = 1
union
select distinct concat(`m`.`login`,'@',`v`.`value`) AS `mail`,
`m`.`mail` AS `alias`
from ((`membres` `m` join `variable` `v`) join `domaines` `d`)
where (`v`.`name` = 'mailname_bounce');


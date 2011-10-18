-- 
-- Because of problems with people using AlternC pre1 , 
-- we include 0.9.10.sql in this file

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

-- 
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

-- type subdomain evolution
ALTER TABLE `sub_domaines` CHANGE `type` `type` VARCHAR(30);
ALTER TABLE `sub_domaines_standby` CHANGE `type` `type` VARCHAR(30);

-- END OF 0.9.10.sql

-- Alter table to allow use of ipv6, cname and txt in dns record
ALTER TABLE sub_domaines DROP PRIMARY KEY;
ALTER TABLE sub_domaines ADD CONSTRAINT pk_SubDomaines PRIMARY KEY (compte,domaine,sub,type,valeur);

-- Alter table mail_domain to add support of temporary mail
ALTER TABLE mail_domain ADD expiration_date datetime DEFAULT null;

-- Domains type
CREATE TABLE IF NOT EXISTS `domaines_type` (
    `name` VARCHAR (255) NOT NULL, -- Uniq name
    `description` TEXT, -- Human description
    `target` enum ('NONE', 'URL', 'DIRECTORY', 'IP', 'IPV6', 'DOMAIN', 'TXT') NOT NULL DEFAULT 'NONE', -- Target type
    `entry` VARCHAR (255) DEFAULT '', -- BIND entry
    `compatibility` VARCHAR (255) DEFAULT '', -- Which type can be on the same subdomains
    `enable` enum ('ALL', 'NONE', 'ADMIN') NOT NULL DEFAULT 'ALL', -- Show this option to who ?
    `only_dns` BOOLEAN DEFAULT FALSE, -- Update_domains modify just the dns, no web configuration
    `need_dns` BOOLEAN DEFAULT TRUE, -- The server need to be the DNS to allow this service
    `advanced` BOOLEAN DEFAULT TRUE, -- It's an advanced option
PRIMARY KEY ( `name` )
) COMMENT = 'Type of domains allowed';

INSERT IGNORE INTO `domaines_type` (name, description, target, entry, compatibility, only_dns, need_dns, advanced, enable) values
('vhost','Locally hosted', 'DIRECTORY', '%SUB% IN A @@PUBLIC_IP@@', 'txt,defmx,defmx2,mx,mx2', false, false, false, 'ALL'),
('url','URL redirection', 'URL', '%SUB% IN A @@PUBLIC_IP@@','txt,defmx,defmx2', true, false, false, 'ALL'),
('ip','IPv4 redirect', 'IP', '%SUB% IN A %TARGET%','url,ip,ipv6,txt,mx,mx2,defmx,defmx2', false, true, false, 'ALL'),
('webmail', 'Webmail access', 'NONE', '%SUB% IN A @@PUBLIC_IP@@', 'txt', false, false, false, 'ALL'),
('ipv6','IPv6 redirect', 'IPV6', '%SUB% IN AAAA %TARGET%','ip,ipv6,webmail,txt,mx,mx2,defmx,defmx2',true, true, true , 'ALL'),
('cname', 'CNAME DNS entry', 'DOMAIN', '%SUB% CNAME %TARGET%', 'txt,mx,mx2,defmx,defmx2',true, true, true , 'ALL'),
('txt', 'TXT DNS entry', 'TXT', '%SUB% IN TXT "%TARGET%"','vhost,url,ip,webmail,ipv6,cname,txt,mx,mx2,defmx,defmx2',true, true, true, 'ALL'),
('mx', 'MX DNS entry', 'DOMAIN', '%SUB% IN MX 5 %TARGET%', 'vhost,url,ip,webmail,ipv6,cname,txt,mx,mx2',true, false, true, 'ALL'),
('mx2', 'secondary MX DNS entry', 'DOMAIN', '%SUB% IN MX 10 %TARGET%', 'vhost,url,ip,webmail,ipv6,cname,txt,mx,mx2',true, false, true, 'ALL'),
('defmx', 'Default mail server', 'NONE', '%SUB% IN MX 5 @@DEFAULT_MX@@.', 'vhost,url,ip,webmail,ipv6,cname,txt,defmx2',true, false, true, 'ADMIN'),
('defmx2', 'Default backup mail server', 'NONE', '%SUB% IN MX 10 @@DEFAULT_SECONDARY_MX@@.', 'vhost,url,ip,webmail,ipv6,cname,txt,defmx',true, false, true, 'ADMIN'),
('panel', 'AlternC panel access', 'NONE', '%SUB% IN A @@PUBLIC_IP@@', 'vhost,url,ip,webmail,ipv6,cname,txt,mx,mx2,defmx,defmx2',true, false, true, 'ALL')
;

-- Changing standby use
ALTER TABLE domaines ADD COLUMN dns_action enum ('OK','UPDATE','DELETE') NOT NULL default 'UPDATE';
ALTER TABLE domaines ADD COLUMN dns_result varchar(255) not null default '';
ALTER TABLE sub_domaines ADD COLUMN web_action enum ('OK','UPDATE','DELETE') NOT NULL default 'UPDATE';
ALTER TABLE sub_domaines ADD COLUMN web_result varchar(255) not null default '';
ALTER TABLE sub_domaines ADD COLUMN enable enum ('ENABLED', 'ENABLE', 'DISABLED', 'DISABLE') NOT NULL DEFAULT 'ENABLED';
DROP TABLE sub_domaines_standby;
DROP TABLE domaines_standby;

UPDATE sub_domaines SET type='VHOST' WHERE type='0'; -- We decide to drop massvhost.
UPDATE sub_domaines SET type='URL' WHERE type='1';
UPDATE sub_domaines SET type='IP' WHERE type='2';
UPDATE sub_domaines SET type='WEBMAIL' WHERE type='3';
UPDATE sub_domaines SET type='IPV6' WHERE type='4';
UPDATE sub_domaines SET type='CNAME' WHERE type='5';
UPDATE sub_domaines SET type='TXT' WHERE type='6';
UPDATE sub_domaines SET web_action='UPDATE';

-- not needed : it's now a subdomain with defmx and/or defmx2 type (this type is admin-only) :
-- ALTER TABLE `domaines` DROP `mx` ;
-- BUT we will remove it in a distant future version : we need it for the migration to take place fluently ...


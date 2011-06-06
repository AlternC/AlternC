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
('vhost','Locally hosted', 'DIRECTORY', '%SUB% IN A @@PUBLIC_IP@@', 'txt', false, false, false, 'ALL'),
('url','URL redirection', 'URL', '%SUB% IN A @@PUBLIC_IP@@','txt', true, true, false, 'ALL'),
('ip','IPv4 redirect', 'IP', '%SUB% IN A %TARGET%','url,ip,ipv6,txt,mx,mx2,defmx,defmx2', false, true, false, 'ALL'),
('webmail', 'Webmail access', 'NONE', '%SUB% IN A @@PUBLIC_IP@@', 'txt', false, false, false, 'ALL'),
('ipv6','IPv6 redirect', 'IPV6', '%SUB% IN AAAA %TARGET%','ip,ipv6,webmail,txt,mx,mx2,defmx,defmx2',true, true, true , 'ALL'),
('cname', 'CNAME DNS entry', 'DOMAIN', '%SUB% CNAME %TARGET%', 'txt,mx,mx2,defmx,defmx2',true, true, true , 'ALL'),
('txt', 'TXT DNS entry', 'TXT', '%SUB% IN TXT "%TARGET%"','vhost,url,ip,webmail,ipv6,cname,txt,mx,mx2,defmx,defmx2',true, true, true, 'ALL'),
('mx', 'MX DNS entry', 'DOMAIN', '%SUB% IN MX 5 %TARGET%', 'vhost,url,ip,webmail,ipv6,cname,txt,mx,mx2',true, false, true, 'ALL'),
('mx2', 'secondary MX DNS entry', 'DOMAIN', '%SUB% IN MX 10 %TARGET%', 'vhost,url,ip,webmail,ipv6,cname,txt,mx,mx2',true, false, true, 'ALL'),
('defmx', 'Default mail server', 'NONE', '%SUB% IN MX 5 @@DEFAULT_MX@@', 'vhost,url,ip,webmail,ipv6,cname,txt,defmx2',true, false, true, 'ADMIN'),
('defmx2', 'Default backup mail server', 'NONE', '%SUB% IN MX 10 @@DEFAULT_SECONDARY_MX@@', 'vhost,url,ip,webmail,ipv6,cname,txt,defmx',true, false, true, 'ADMIN'),
('panel', 'AlternC panel access', 'NONE', '%SUB% IN A @@PUBLIC_IP@@', 'vhost,url,ip,webmail,ipv6,cname,txt,mx,mx2',true, false, true, 'ALL')
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
ALTER TABLE `domaines` DROP `mx` ;


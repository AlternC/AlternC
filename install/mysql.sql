--
-- ----------------------------------------------------------------------
-- AlternC - Web Hosting System
-- Copyright (C) 2000-2012 by the AlternC Development Team.
-- https://alternc.org/
-- ----------------------------------------------------------------------
-- LICENSE
--
-- This program is free software; you can redistribute it and/or
-- modify it under the terms of the GNU General Public License (GPL)
-- as published by the Free Software Foundation; either version 2
-- of the License, or (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- To read the license please visit http://www.gnu.org/copyleft/gpl.html
-- ----------------------------------------------------------------------
-- Purpose of file: Create the basic structure for the mysql system db
-- ----------------------------------------------------------------------
--

-- ----------------------------------------------------------------------
-- STRUCTURE DES TABLES D`ALTERNC
--
-- IMPORTANT: lorsque la structure de ces tables est modifiée, le
-- fichier upgrades/<version>.sql doit être modifié (ou créé!) pour que
-- les installations courantes soient mises à jour. <version> est ici
-- le prochain numéro de version d`AlternC. Voir upgrades/README pour
-- plus de détails.
-- ----------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `slaveip` (
`ip` VARCHAR( 40 ) NOT NULL ,
`class` TINYINT NOT NULL ,
PRIMARY KEY ( `ip` , `class` )
) ENGINE=MyISAM COMMENT = 'Allowed ip for slave dns managment';

CREATE TABLE IF NOT EXISTS `slaveaccount` (
`login` VARCHAR( 64 ) NOT NULL ,
`pass`  VARCHAR( 64 ) NOT NULL ,
PRIMARY KEY ( `login` )
) ENGINE=MyISAM COMMENT = 'Allowed account for slave dns managment';


--
-- Structure de la table `browser`
--
-- Cette table contient les préférences des utilisateurs dans le gestionnaire de fichiers


CREATE TABLE IF NOT EXISTS browser (
  uid int(10) unsigned NOT NULL default '0',		-- Numéro de l`utilisateur
  editsizex int(10) unsigned NOT NULL default '0',	-- Largeur de la zone d`edition du brouteur
  editsizey int(10) unsigned NOT NULL default '0',	-- Hauteur de la zone d`edition du brouteur
  listmode tinyint(3) unsigned NOT NULL default '0',	-- Mode de listing (1 colonne, 2 colonne, 3 colonne)
  showicons tinyint(4) NOT NULL default '0',		-- Faut-il afficher les icones (1/0)
  downfmt tinyint(4) NOT NULL default '0',		-- Format de téléchargement (zip/bz2/tgz/tar.Z)
  createfile tinyint(4) NOT NULL default '0',		-- Que fait-on après création d`un fichier (1/0)
  showtype tinyint(4) NOT NULL default '0',		-- Affiche-t-on le type mime ? 
  editor_font varchar(64) NOT NULL default '',		-- Nom de la police dans l`éditeur de fichiers
  editor_size varchar(8) NOT NULL default '',		-- Taille de la police dans l`éditeur de fichiers
  crff tinyint(4) NOT NULL default '0',			-- mémorise le dernier fichier/dossier créé (pour le bouton radio)
  golastdir tinyint(4) NOT NULL default '0',		-- Faut-il aller au dernier dossier ou au dossier racine dans le brouteur ?
  lastdir varchar(255) NOT NULL default '',		-- Dernier dossier visité.
  PRIMARY KEY  (uid)
) ENGINE=MyISAM COMMENT='Préférences du gestionnaire de fichiers';


--
-- Structure de la table `chgmail`
--
-- Cette table contient les demandes de changements de mail pour les membres

CREATE TABLE IF NOT EXISTS chgmail (
  uid int(10) unsigned NOT NULL default '0',		-- Numéro de l`utilisateur
  cookie varchar(20) NOT NULL default '',		-- Cookie du mail
  ckey varchar(6) NOT NULL default '',			-- Clé de vérif
  mail varchar(128) NOT NULL default '',		-- Nouvel Email
  ts int(10) unsigned NOT NULL default '0',		-- Timestamp de la demande 
  PRIMARY KEY  (uid)
) ENGINE=MyISAM COMMENT='Demandes de changements de mail en cours';

--
-- Structure de la table `db`
--
-- Contient les bases mysql des membres, + login / pass en clair

CREATE TABLE IF NOT EXISTS db (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  uid int(10) unsigned NOT NULL default '0',		-- Numéro de l`utilisateur
  login varchar(16) NOT NULL default '',		-- Nom d`utilisateur mysql
  pass varchar(16) NOT NULL default '',			-- Mot de passe mysql
  db varchar(64) NOT NULL default '',			-- Base de données concernée
  bck_mode tinyint(3) unsigned NOT NULL default '0',	-- Mode de backup (0/non 1/Daily 2/Weekly)
  bck_history tinyint(3) unsigned NOT NULL default '0',	-- Nombre de backup à conserver ?
  bck_gzip tinyint(3) unsigned NOT NULL default '0',	-- Faut-il compresser les backups ?
  bck_dir varchar(255) NOT NULL default '',		-- Où stocke-t-on les backups sql ?
  PRIMARY KEY id (id)
) ENGINE=MyISAM COMMENT='Bases MySQL des membres';

--
-- Structure de la table `domaines`
--
-- Liste des domaines hébergés

CREATE TABLE IF NOT EXISTS domaines (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  compte int(10) unsigned NOT NULL default '0',
  domaine varchar(64) NOT NULL default '',
  gesdns int(1) NOT NULL default '1',
  gesmx int(1) NOT NULL default '1',
  noerase tinyint(4) NOT NULL default '0',
  dns_action enum ('OK','UPDATE','DELETE') NOT NULL default 'UPDATE',
  dns_result varchar(255) not null default '',
  zonettl int(10) unsigned NOT NULL default '86400',
  PRIMARY KEY (id),
  UNIQUE KEY (domaine)
) ENGINE=MyISAM;

--
-- Structure de la table `ftpusers`
--
-- Comptes ftp des membres

CREATE TABLE IF NOT EXISTS ftpusers (
  id int(10) unsigned NOT NULL auto_increment,
  name varchar(64) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  encrypted_password VARCHAR(32) default NULL,
  homedir varchar(128) NOT NULL default '',
  uid int(10) unsigned NOT NULL default '0',
  enabled boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY  (id),
  UNIQUE KEY name (name),
  KEY homedir (homedir),
  KEY mid (uid)
) ENGINE=MyISAM;

--
-- Structure de la table `local`
--
-- Champs utilisables par l`hébergeur pour associer des données locales aux membres.

CREATE TABLE IF NOT EXISTS local (
  uid int(10) unsigned NOT NULL default '0',
  nom varchar(128) NOT NULL default '',
  prenom varchar(128) NOT NULL default '',
  PRIMARY KEY  (uid)
) ENGINE=MyISAM COMMENT='Parametres Locaux des membres';

--
-- Structure de la table `membres`
--
-- Liste des membres

CREATE TABLE IF NOT EXISTS membres (
  uid int(10) unsigned NOT NULL auto_increment,		-- Numéro du membre (GID)
  login varchar(128) NOT NULL default '',		-- Nom d`utilisateur
  pass varchar(64) NOT NULL default '',			-- Mot de passe
  enabled tinyint(4) NOT NULL default '1',		-- Le compte est-il actif ?
  su tinyint(4) NOT NULL default '0',			-- Le compte est-il super-admin ?
  mail varchar(128) NOT NULL default '',		-- Adresse email du possesseur
  lastaskpass int(10) unsigned default '0',		-- Date de dernière demande du pass par mail
  show_help tinyint(4) NOT NULL default '1',		-- Faut-il afficher l`aide dans le bureau
  lastlogin datetime NOT NULL default '0000-00-00 00:00:00',	-- Date du dernier login
  lastfail tinyint(4) NOT NULL default '0',		-- Nombre d`échecs depuis le dernier login
  lastip varchar(255) NOT NULL default '',		-- Nom DNS du client au dernier login
  creator int(10) unsigned default '0',			-- Qui a créé le compte (quel uid admin)
  canpass tinyint(4) default '1',			-- L`utilisateur peut-il changer son pass.
  warnlogin tinyint(4) default '0',			-- TODO L`utilisateur veut-il recevoir un mail quand on se loggue sur son compte ?
  warnfailed tinyint(4) default '0',			-- TODO L`utilisateur veut-il recevoir un mail quand on tente de se logguer sur son compte ?
  admlist tinyint(4) default '0',			-- Mode d`affichage de la liste des membres pour les super admins
  type varchar(128) default 'default',
  db_server_id int(10) DEFAULT NULL,
  notes TEXT NOT NULL,
  created datetime default NULL, 
  renewed datetime default NULL, 
  duration int(4) default NULL,
  PRIMARY KEY  (uid),
  UNIQUE KEY k_login (login)
) ENGINE=MyISAM COMMENT='Liste des membres du serveur';

--
-- Structure de la table `quotas`
--
-- Listes des quotas des membres

CREATE TABLE IF NOT EXISTS quotas (
  uid int(10) unsigned NOT NULL default '0',		-- Numéro GID du membre concerné
  name varchar(64) NOT NULL default '',			-- Nom du quota
  total int(10) unsigned NOT NULL default '0',	-- Quota total (maximum autorisé)
  PRIMARY KEY  (uid,name)
) ENGINE=MyISAM COMMENT='Quotas des Membres';

--
-- Structure de la table `sessions`
--
-- Sessions actives sur le bureau

CREATE TABLE IF NOT EXISTS sessions (
  sid varchar(32) NOT NULL default '',			-- Cookie de session (md5)
  uid int(10) unsigned NOT NULL default '0',		-- UID du membre concerné
  ip varchar(40) NOT NULL default '',		-- Adresse IP de la connexion
  ts timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM COMMENT='Session actives sur le bureau';

--
-- Structure de la table `sub_domaines`
--
-- Sous-domaines des membres

CREATE TABLE IF NOT EXISTS sub_domaines (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  compte int(10) unsigned NOT NULL default '0',
  domaine varchar(255) NOT NULL default '',
  sub varchar(100) NOT NULL default '',
  valeur varchar(255) default NULL,
  type varchar(30) NOT NULL default 'LOCAL',
  web_action enum ('OK','UPDATE','DELETE') NOT NULL default 'UPDATE',
  web_result varchar(255) not null default '',
  enable enum ('ENABLED', 'ENABLE', 'DISABLED', 'DISABLE') NOT NULL DEFAULT 'ENABLED',
  PRIMARY KEY (id)
--  ,FOREIGN KEY (type) REFERENCES (domaines_type)
) ENGINE=MyISAM;

--
-- Main address table.
--
-- Addresses for domain.

CREATE TABLE IF NOT EXISTS `address` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT, -- Technical id.
  `domain_id` int(10) unsigned NOT NULL REFERENCES `domaines`(`id`), -- FK to domaines.
  `address` varchar(255) NOT NULL, -- The address.
  `type` char(8) NOT NULL, -- standard emails are '', other may be 'mailman' or 'sympa' ...
  `password` varchar(255) DEFAULT NULL, -- The password associated to the address.
  `enabled` int(1) unsigned NOT NULL DEFAULT '1', -- Enabled flag.
  `expire_date` datetime DEFAULT NULL, -- Expiration date, used for temporary addresses.
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Update date, for technical usage only.
  `mail_action` enum('OK','DELETE','DELETING') NOT NULL default 'OK', -- mail_action is DELETE or DELETING when deleting a mailbox by cron
  PRIMARY KEY (`id`),
  UNIQUE INDEX `fk_domain_id` (`domain_id`,`address`)
) ENGINE=MyISAM COMMENT = 'This is the main address table. It represents an address as in RFC2822';

--
-- Mailbox table.
-- 
-- Local delivered mailboxes.

CREATE TABLE IF NOT EXISTS `mailbox` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT, -- Technical id.
  `address_id` int(10) unsigned NOT NULL REFERENCES `address`(`id`), -- Reference to address.
  `path` varchar(255) NOT NULL, -- Relative path to the mailbox.
  `quota` int(10) unsigned DEFAULT NULL, -- Quota for this mailbox.
  `delivery` varchar(255) NOT NULL, -- Delivery transport.
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Update date, for technical usage only.
  `bytes` int(10) NOT NULL DEFAULT '0', -- number of bytes in the mailbox, filled by dovecot
  `messages` int(11) NOT NULL DEFAULT '0', -- number of messages in the mailbox, filled by dovecot 
  `lastlogin` datetime NOT NULL, -- Last login, filled by dovecot
  `mail_action` enum('OK','DELETE','DELETING') NOT NULL default 'OK', -- mail_action is DELETE or DELETING when deleting a mailbox by cron
  PRIMARY KEY (`id`),
  UNIQUE KEY `address_id` (`address_id`)
) ENGINE=MyISAM COMMENT = 'Table containing local deliverd mailboxes.';

--
-- Other recipients.
--
-- Other recipients for an address (aliases)

CREATE TABLE IF NOT EXISTS `recipient` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT, -- Technical id.
  `address_id` int(10) unsigned NOT NULL REFERENCES `address`(`id`), -- Reference to address
  `recipients` text NOT NULL, -- Recipients
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Update date, for technical usage only.
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_id` (`id`,`address_id`)
) ENGINE=MyISAM COMMENT = 'Table containing other recipients (aliases) for an address.';


--
-- Structure de la table `defquotas`
--
-- Quotas par défaut pour les services

CREATE TABLE IF NOT EXISTS defquotas (
  quota varchar(128),				-- Nom du quota
  value int(10) unsigned default '0',	-- Valeur du quota
  type  varchar(128) default 'default',		-- Type de compte associée à ce quota
  PRIMARY KEY (quota,type)
) ENGINE=MyISAM;

--
-- Quotas par defaut pour les nouveaux membres
--
-- Ces quotas par defaut sont redefinissables dans l`interface web

INSERT IGNORE INTO defquotas (quota,value) VALUES ('dom',1);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('web',51200);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('mail',10);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('ftp',2);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('stats',1);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('mysql',1);


--
-- Structure de la table `forbidden_domains`
--
-- Liste des domaines explicitement interdits sur le serveur :

CREATE TABLE IF NOT EXISTS forbidden_domains (
  domain varchar(255) NOT NULL default '',
  PRIMARY KEY  (domain)
) ENGINE=MyISAM COMMENT='forbidden domains to install';

--
-- Contenu de la table `forbidden_domains`
--

-- Registries : 
INSERT IGNORE INTO forbidden_domains VALUES ('afilias.net');
INSERT IGNORE INTO forbidden_domains VALUES ('afnic.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('dns.be');
INSERT IGNORE INTO forbidden_domains VALUES ('internic.net');
INSERT IGNORE INTO forbidden_domains VALUES ('netsol.com');
INSERT IGNORE INTO forbidden_domains VALUES ('nic.biz');
INSERT IGNORE INTO forbidden_domains VALUES ('nic.cx');
INSERT IGNORE INTO forbidden_domains VALUES ('nic.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('verisign.com');
INSERT IGNORE INTO forbidden_domains VALUES ('octopuce.com');
INSERT IGNORE INTO forbidden_domains VALUES ('pir.org');
INSERT IGNORE INTO forbidden_domains VALUES ('cira.ca');
-- big isp :
INSERT IGNORE INTO forbidden_domains VALUES ('aol.com');
INSERT IGNORE INTO forbidden_domains VALUES ('hotmail.com');
INSERT IGNORE INTO forbidden_domains VALUES ('microsoft.com');
INSERT IGNORE INTO forbidden_domains VALUES ('sympatico.ca');
INSERT IGNORE INTO forbidden_domains VALUES ('tiscali.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('voila.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('wanadoo.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('yahoo.com');
INSERT IGNORE INTO forbidden_domains VALUES ('yahoo.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('gmail.com');
INSERT IGNORE INTO forbidden_domains VALUES ('orange.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('sfr.fr');

--
-- Structure de la table `tld`
--
-- Liste des tld autorisés sur ce serveur : 

CREATE TABLE IF NOT EXISTS tld (
  tld varchar(128) NOT NULL default '',		-- lettres du tld (sans le .)
  mode tinyint(4) NOT NULL default '0',		-- Comment est-il autorisé ?
  PRIMARY KEY  (tld),
  KEY mode (mode)
) ENGINE=MyISAM COMMENT='TLD autorises et comment sont-ils autorises ? ';

--
-- Contenu de la table `tld`
--

INSERT IGNORE INTO tld VALUES ('fr', 4);
INSERT IGNORE INTO tld VALUES ('com', 1);
INSERT IGNORE INTO tld VALUES ('org', 1);
INSERT IGNORE INTO tld VALUES ('net', 1);
INSERT IGNORE INTO tld VALUES ('biz', 1);
INSERT IGNORE INTO tld VALUES ('info', 1);
INSERT IGNORE INTO tld VALUES ('name', 1);
INSERT IGNORE INTO tld VALUES ('ca', 1);
INSERT IGNORE INTO tld VALUES ('it', 1);
INSERT IGNORE INTO tld VALUES ('ws', 1);
INSERT IGNORE INTO tld VALUES ('be', 1);
INSERT IGNORE INTO tld VALUES ('eu.org', 4);
INSERT IGNORE INTO tld VALUES ('cjb.net', 4);
INSERT IGNORE INTO tld VALUES ('asso.fr', 4);
INSERT IGNORE INTO tld VALUES ('eu', 1);
INSERT IGNORE INTO tld VALUES ('coop', 1);
INSERT IGNORE INTO tld VALUES ('asia', 1);

--
-- Table structure for table 'variable'
--
-- if comment is null, then the variable is internal and will not show
-- up in the generic configuration panel
CREATE TABLE `variable` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(48) NOT NULL DEFAULT '',
  `value` longtext NOT NULL,
  `comment` mediumtext,
  `strata` enum('DEFAULT','GLOBAL','FQDN','FQDN_CREATOR','CREATOR','MEMBER','DOMAIN') NOT NULL DEFAULT 'DEFAULT',
  `strata_id` int(10) DEFAULT NULL,
  `type` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_2` (`name`,`strata`,`strata_id`),
  KEY `name` (`name`)
) ENGINE=MyISAM;

-- hosting_tld: only used, for now, in bureau/admin/adm_*add.php
INSERT IGNORE INTO `variable` (name, value, comment) VALUES ('hosting_tld', 0,
'This is a FQDN that designates the main hostname of the service.

For example, hosting_tld determines in what TLD the "free" user domain
is created. If this is set to "example.com", a checkbox will appear in
the user creation dialog requesting the creator if he wants to create
the domain "username.example.com".

If this is set to 0 or a "false" string, it will be ignored.');

INSERT IGNORE INTO `variable` (name, value, comment) VALUES ('mailname_bounce', '',
'FQDN of the mail server, used to create vhost virtual mail_adress.');

--
-- Table structure for table `dbusers`
--

CREATE TABLE IF NOT EXISTS `dbusers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(10) unsigned NOT NULL default '0',
  `name` varchar(16) NOT NULL default '',
  `password`  varchar( 64 ),
  `enable` enum ('ACTIVATED', 'HIDDEN', 'ADMIN') NOT NULL DEFAULT 'ACTIVATED', 
  KEY `id` (`id`)
) ENGINE=MyISAM COMMENT='Utilisateurs MySQL des membres';


CREATE TABLE IF NOT EXISTS `mxaccount` (
`login` VARCHAR( 64 ) NOT NULL ,
`pass`  VARCHAR( 64 ) NOT NULL ,
PRIMARY KEY ( `login` )
) ENGINE=MyISAM COMMENT = 'Allowed account for secondary mx managment';


-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `size_web` (
  `uid` int(10) unsigned NOT NULL default '0',
  `size` int(10) unsigned NOT NULL default '0',
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (`uid`),
  KEY `ts` (`ts`)
) ENGINE=MyISAM COMMENT='Web space used by accounts.';

-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `size_db` (
  `db` varchar(255) NOT NULL default '',
  `size` int(10) unsigned NOT NULL default '0',
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (`db`),
  KEY `ts` (`ts`)
) ENGINE=MyISAM COMMENT='MySQL Database used space';

-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `size_mailman` (
  `list` varchar(255) NOT NULL default '',
  `uid` int(11) NOT NULL default '0',
  `size` int(10) unsigned NOT NULL default '0',
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`list`),
  KEY `ts` (`ts`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM COMMENT='Mailman Lists used space';

-- --------------------------------------------------------


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
    `create_tmpdir` BOOLEAN NOT NULL DEFAULT FALSE, -- do we create tmp dir ?
    `create_targetdir` BOOLEAN NOT NULL DEFAULT FALSE, -- do we create target dir ?
PRIMARY KEY ( `name` )
) ENGINE=MyISAM COMMENT = 'Type of domains allowed';

INSERT IGNORE INTO `domaines_type` (name, description, target, entry,                             compatibility,                               only_dns, need_dns, advanced, enable) values
('vhost',  'Locally hosted',             'DIRECTORY', '%SUB% IN A @@PUBLIC_IP@@',                 'txt,defmx,defmx2,mx,mx2',                   false,    false,    false, 'ALL'),
('url',    'URL redirection',            'URL',       '%SUB% IN A @@PUBLIC_IP@@',                 'txt,defmx,defmx2',                          false,    false,    false, 'ALL'),
('ip',     'IPv4 redirect',              'IP',        '%SUB% IN A %TARGET%',                      'url,ip,ipv6,txt,mx,mx2,defmx,defmx2',       true,     true,     false, 'ALL'),
('ipv6',   'IPv6 redirect',              'IPV6',      '%SUB% IN AAAA %TARGET%',                   'ip,ipv6,txt,mx,mx2,defmx,defmx2',           true,     true,     true,  'ALL'),
('cname',  'CNAME DNS entry',            'DOMAIN',    '%SUB% CNAME %TARGET%',                     '',                                          true,     true,     true,  'ALL'),
('txt',    'TXT DNS entry',              'TXT',       '%SUB% IN TXT "%TARGET%"',                  'vhost,url,ip,ipv6,txt,mx,mx2,defmx,defmx2', true,     true,     true,  'ALL'),
('mx',     'MX DNS entry',               'DOMAIN',    '%SUB% IN MX 5 %TARGET%',                   'vhost,url,ip,ipv6,txt,mx,mx2',              true,     true,     true,  'ALL'),
('mx2',    'secondary MX DNS entry',     'DOMAIN',    '%SUB% IN MX 10 %TARGET%',                  'vhost,url,ip,ipv6,txt,mx,mx2',              true,     true,     true,  'ALL'),
('defmx',  'Default mail server',        'NONE',      '%SUB% IN MX 5 @@DEFAULT_MX@@.',            'vhost,url,ip,ipv6,txt,defmx2',              true,     true,     true,  'ADMIN'),
('defmx2', 'Default backup mail server', 'NONE',      '%SUB% IN MX 10 @@DEFAULT_SECONDARY_MX@@.', 'vhost,url,ip,ipv6,txt,defmx',               true,     true,     true,  'ADMIN'),
('panel',  'AlternC panel access',       'NONE',      '%SUB% IN A @@PUBLIC_IP@@',                 'vhost,url,ip,ipv6,txt,mx,mx2,defmx,defmx2', false,    false,    true,  'ALL')
;
UPDATE domaines_type SET create_tmpdir=true, create_targetdir=true WHERE target='DIRECTORY';

-- Add function who are not in mysql 5 to be able ton convert ipv6 to decimal (and reverse it)
DELIMITER //
DROP FUNCTION IF EXISTS INET_ATON6;//
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
DROP FUNCTION IF EXISTS INET_NTOA6;//
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
-- Structure de la table `cron`
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

-- Generate all the alias configured by the users
select 
  concat(`address`.`address`,'@',`domaines`.`domaine`) AS `mail`,
  concat(if(isnull(`mailbox`.`id`),
  '',
  concat(concat(`address`.`address`,'@',`domaines`.`domaine`),'\n')),
  `recipient`.`recipients`) AS `alias`
from 
  (
    ((`recipient` join `address` on((`address`.`id` = `recipient`.`address_id`)))
    left join `mailbox` on((`mailbox`.`address_id` = `address`.`id`))
    )
    join `domaines` on((`domaines`.`id` = `address`.`domain_id`))
  )
where 
  `address`.`enabled` = 1

UNION

-- Generate the alias for all the account
-- Example : account gaylord will have gaylord@FQDN
-- as an alias to his email account. FQDN can be
-- changed in variable mailname_bounce
select 
  distinct concat(`m`.`login`,'@',`v`.`value`) AS `mail`,
  `m`.`mail` AS `alias`
from 
  `membres` `m`,
  `variable` `v`
where 
  `v`.`name` = 'mailname_bounce'

UNION

-- Generate an alias alterncpanel@FQDN to admin mail
select 
  distinct concat('alterncpanel','@',`v`.`value`) AS `mail`,
  `m`.`mail` AS `alias`
from 
  `membres` `m`,
  `variable` `v`
where 
  (`v`.`name` = 'mailname_bounce' AND `m`.`uid`=2000)

;

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

-- Defaults subdomains to create when a domain is added
CREATE TABLE IF NOT EXISTS `default_subdomains` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sub` varchar(255) NOT NULL,
  `domain_type` varchar(255) NOT NULL,
  `domain_type_parameter` varchar(255) NOT NULL,
  `concerned` enum('BOTH','MAIN','SLAVE') NOT NULL DEFAULT 'MAIN',
  `enabled` boolean not null default true,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM COMMENT='Contains the defaults subdomains created on domains creation';

INSERT IGNORE INTO `default_subdomains` (`sub`, `domain_type`, `domain_type_parameter`, `concerned`) VALUES
('www', 'VHOST', '%%DOMAINDIR%%', 'MAIN'),
('mail', 'WEBMAIL', '', 'MAIN'),
('', 'URL', 'http://www.%%DOMAIN%%', 'MAIN'),
('www', 'URL', 'http://www.%%TARGETDOM%%', 'SLAVE'),
('mail', 'URL', 'http://mail.%%TARGETDOM%%', 'SLAVE'),
('', 'URL', 'http://%%TARGETDOM%%', 'SLAVE');


-- Table for the MySQL servers
CREATE TABLE IF NOT EXISTS `db_servers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `host` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `client` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM COMMENT='List of the databases servers';

-- Table for VM requests
CREATE TABLE IF NOT EXISTS `vm_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(256) NOT NULL,
  `date_start` datetime NOT NULL,
  `date_end` datetime DEFAULT NULL,
  `uid` int(10) unsigned NOT NULL,
  `serialized_object` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_end` (`date_end`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM COMMENT='VM Allocation requests';


CREATE TABLE IF NOT EXISTS `actions` (
 id int(10) unsigned NOT NULL AUTO_INCREMENT,
 type enum ('CREATE_FILE','FIX_USER','CREATE_DIR','DELETE','MOVE','FIX_DIR','FIX_FILE'),
 parameters longtext default NULL,
 creation timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 begin timestamp,
 end timestamp,
 user varchar(255) default NULL,
 status int(8) unsigned default NULL,
 PRIMARY KEY ( `id` )
) ENGINE=MyISAM COMMENT = 'generic actions';

#
# $Id: mysql.sql,v 1.39 2006/02/17 15:15:54 olivier Exp $
# ----------------------------------------------------------------------
# AlternC - Web Hosting System
# Copyright (C) 2006 Le rseau Koumbit Inc.
# http://koumbit.org/
# Copyright (C) 2002 by the AlternC Development Team.
# http://alternc.org/
# ----------------------------------------------------------------------
# Based on:
# Valentin Lacambre`s web hosting softwares: http://altern.org/
# ----------------------------------------------------------------------
# LICENSE
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License (GPL)
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# To read the license please visit http://www.gnu.org/copyleft/gpl.html
# ----------------------------------------------------------------------
# Original Author of file: Benjamin Sonntag
# Purpose of file: Create the basic structure for the mysql system db
# ----------------------------------------------------------------------
#

######################################################################
# STRUCTURE DES TABLES D`ALTERNC
#
# IMPORTANT: lorsque la structure de ces tables est modifie, le
# fichier upgrades/<version>.sql doit tre modifi (ou cr!) pour que
# les installations courantes soient mises  jour. <version> est ici
# le prochain numro de version d`AlternC. Voir upgrades/README pour
# plus de dtails.
#########################################################################

CREATE TABLE IF NOT EXISTS `slaveip` (
`ip` VARCHAR( 40 ) NOT NULL ,
`class` TINYINT NOT NULL ,
PRIMARY KEY ( `ip` , `class` )
) COMMENT = 'Allowed ip for slave dns managment';

CREATE TABLE IF NOT EXISTS `slaveaccount` (
`login` VARCHAR( 64 ) NOT NULL ,
`pass`  VARCHAR( 64 ) NOT NULL ,
PRIMARY KEY ( `login` )
) COMMENT = 'Allowed account for slave dns managment';



#
# Structure de la table `browser`
#
# Cette table contient les prfrences des utilisateurs dans le gestionnaire de fichiers


CREATE TABLE IF NOT EXISTS browser (
  uid int(10) unsigned NOT NULL default '0',		# Numro de l`utilisateur
  editsizex int(10) unsigned NOT NULL default '0',	# Largeur de la zone d`edition du brouteur
  editsizey int(10) unsigned NOT NULL default '0',	# Hauteur de la zone d`edition du brouteur
  listmode tinyint(3) unsigned NOT NULL default '0',	# Mode de listing (1 colonne, 2 colonne, 3 colonne)
  showicons tinyint(4) NOT NULL default '0',		# Faut-il afficher les icones (1/0)
  downfmt tinyint(4) NOT NULL default '0',		# Format de tlchargement (zip/bz2/tgz/tar.Z)
  createfile tinyint(4) NOT NULL default '0',		# Que fait-on aprs cration d`un fichier (1/0)
  showtype tinyint(4) NOT NULL default '0',		# Affiche-t-on le type mime ? 
  editor_font varchar(64) NOT NULL default '',		# Nom de la police dans l`diteur de fichiers
  editor_size varchar(8) NOT NULL default '',		# Taille de la police dans l`diteur de fichiers
  crff tinyint(4) NOT NULL default '0',			# mmorise le dernier fichier/dossier cr (pour le bouton radio)
  golastdir tinyint(4) NOT NULL default '0',		# Faut-il aller au dernier dossier ou au dossier racine dans le brouteur ?
  lastdir varchar(255) NOT NULL default '',		# Dernier dossier visit.
  PRIMARY KEY  (uid)
) TYPE=MyISAM COMMENT='Prfrences du gestionnaire de fichiers';


#
# Structure de la table `chgmail`
#
# Cette table contient les demandes de changements de mail pour les membres

CREATE TABLE IF NOT EXISTS chgmail (
  uid int(10) unsigned NOT NULL default '0',		# Numro de l`utilisateur
  cookie varchar(20) NOT NULL default '',		# Cookie du mail
  ckey varchar(6) NOT NULL default '',			# Cl de vrif
  mail varchar(128) NOT NULL default '',		# Nouvel Email
  ts bigint(20) unsigned NOT NULL default '0',		# Timestamp de la demande 
  PRIMARY KEY  (uid)
) TYPE=MyISAM COMMENT='Demandes de changements de mail en cours';

#
# Structure de la table `db`
#
# Contient les bases mysql des membres, + login / pass en clair

CREATE TABLE IF NOT EXISTS db (
  uid int(10) unsigned NOT NULL default '0',		# Numro de l`utilisateur
  login varchar(16) NOT NULL default '',		# Nom d`utilisateur mysql
  pass varchar(16) NOT NULL default '',			# Mot de passe mysql
  db varchar(64) NOT NULL default '',			# Base de donnes concerne
  bck_mode tinyint(3) unsigned NOT NULL default '0',	# Mode de backup (0/non 1/Daily 2/Weekly)
  bck_history tinyint(3) unsigned NOT NULL default '0',	# Nombre de backup  conserver ?
  bck_gzip tinyint(3) unsigned NOT NULL default '0',	# Faut-il compresser les backups ?
  bck_dir varchar(255) NOT NULL default '',		# O stocke-t-on les backups sql ?
  KEY uid (uid)
) TYPE=MyISAM COMMENT='Bases MySQL des membres';

--
-- Structure de la table `domaines`
--
-- Liste des domaines heberges

CREATE TABLE IF NOT EXISTS domaines (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  compte int(10) unsigned NOT NULL default '0',
  domaine varchar(64) NOT NULL default '',
  gesdns int(1) NOT NULL default '1',
  gesmx int(1) NOT NULL default '1',
  noerase tinyint(4) NOT NULL default '0',
  dns_action enum ('OK','UPDATE','DELETE') NOT NULL default 'UPDATE',
  dns_result varchar(255) not null default '',
  PRIMARY KEY (id),
  UNIQUE KEY (domaine)
) TYPE=MyISAM;

#
# Structure de la table `ftpusers`
#
# Comptes ftp des membres

CREATE TABLE IF NOT EXISTS ftpusers (
  id int(10) unsigned NOT NULL auto_increment,
  name varchar(64) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  encrypted_password VARCHAR(32) default NULL,
  homedir varchar(128) NOT NULL default '',
  uid int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY name (name),
  KEY homedir (homedir),
  KEY mid (uid)
) TYPE=MyISAM;

#
# Structure de la table `local`
#
# Champs utilisables par l`hbergeur pour associer des donnes locales aux membres.

CREATE TABLE IF NOT EXISTS local (
  uid int(10) unsigned NOT NULL default '0',
  nom varchar(128) NOT NULL default '',
  prenom varchar(128) NOT NULL default '',
  PRIMARY KEY  (uid)
) TYPE=MyISAM COMMENT='Parametres Locaux des membres';

#
# Structure de la table `membres`
#
# Liste des membres

CREATE TABLE IF NOT EXISTS membres (
  uid int(10) unsigned NOT NULL auto_increment,		# Numro du membre (GID)
  login varchar(128) NOT NULL default '',		# Nom d`utilisateur
  pass varchar(64) NOT NULL default '',			# Mot de passe
  enabled tinyint(4) NOT NULL default '1',		# Le compte est-il actif ?
  su tinyint(4) NOT NULL default '0',			# Le compte est-il super-admin ?
  mail varchar(128) NOT NULL default '',		# Adresse email du possesseur
  lastaskpass bigint(20) unsigned default '0',		# Date de dernire demande du pass par mail
  show_help tinyint(4) NOT NULL default '1',		# Faut-il afficher l`aide dans le bureau
  lastlogin datetime NOT NULL default '0000-00-00 00:00:00',	# Date du dernier login
  lastfail tinyint(4) NOT NULL default '0',		# Nombre d`checs depuis le dernier login
  lastip varchar(255) NOT NULL default '',		# Nom DNS du client au dernier login
  creator int(10) unsigned default '0',			# Qui a cr le compte (quel uid admin)
  canpass tinyint(4) default '1',			# L`utilisateur peut-il changer son pass.
  warnlogin tinyint(4) default '0',			# TODO L`utilisateur veut-il recevoir un mail quand on se loggue sur son compte ?
  warnfailed tinyint(4) default '0',			# TODO L`utilisateur veut-il recevoir un mail quand on tente de se logguer sur son compte ?
  admlist tinyint(4) default '0',			# Mode d`affichage de la liste des membres pour les super admins
  type varchar(128) default 'default',
  notes TEXT NOT NULL,
  created datetime default NULL, 
  renewed datetime default NULL, 
  duration int(4) default NULL,
  PRIMARY KEY  (uid),
  UNIQUE KEY k_login (login)
) TYPE=MyISAM COMMENT='Liste des membres du serveur';

#
# Structure de la table `quotas`
#
# Listes des quotas des membres

CREATE TABLE IF NOT EXISTS quotas (
  uid int(10) unsigned NOT NULL default '0',		# Numro GID du membre concern
  name varchar(64) NOT NULL default '',			# Nom du quota
  total bigint(20) unsigned NOT NULL default '0',	# Quota total (maximum autoris)
  PRIMARY KEY  (uid,name)
) TYPE=MyISAM COMMENT='Quotas des Membres';

#
# Structure de la table `sessions`
#
# Sessions actives sur le bureau

CREATE TABLE IF NOT EXISTS sessions (
  sid varchar(32) NOT NULL default '',			# Cookie de session (md5)
  uid int(10) unsigned NOT NULL default '0',		# UID du membre concern
  ip varchar(40) NOT NULL default '',		# Adresse IP de la connexion
  ts timestamp(14) NOT NULL
) TYPE=MyISAM COMMENT='Session actives sur le bureau';

--
-- Structure de la table `sub_domaines`
--
-- Sous-domaines des membres

CREATE TABLE IF NOT EXISTS sub_domaines (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  compte int(10) unsigned NOT NULL default '0',
  domaine varchar(64) NOT NULL default '',
  sub varchar(100) NOT NULL default '',
  valeur varchar(255) default NULL,
  type varchar(30) NOT NULL default 'LOCAL',
  web_action enum ('OK','UPDATE','DELETE') NOT NULL default 'UPDATE',
  web_result varchar(255) not null default '',
  enable enum ('ENABLED', 'ENABLE', 'DISABLED', 'DISABLE') NOT NULL DEFAULT 'ENABLED',
  PRIMARY KEY (id),
  UNIQUE (compte,domaine,sub,type,valeur)
--  ,FOREIGN KEY (type) REFERENCES (domaines_type)
) TYPE=MyISAM;

--
-- Main address table.
--
-- Addresses for domain.

CREATE TABLE `address` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, -- Technical id.
  `domain_id` bigint(20) unsigned NOT NULL REFERENCES `domaines`(`id`), -- FK to sub_domains.
  `address` varchar(255) NOT NULL, -- The address.
  `password` varchar(255) DEFAULT NULL, -- The password associated to the address.
  `enabled` int(1) unsigned NOT NULL DEFAULT '1', -- Enabled flag.
  `expire_date` datetime DEFAULT NULL, -- Expiration date, used for temporary addresses.
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Update date, for technical usage only.
  PRIMARY KEY (`id`),
  UNIQUE INDEX `domain_id_idx` (`domain_id`),
  UNIQUE KEY `address` (`address`)
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
  UNIQUE KEY `key_id` (`id`,`address_id`)
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

#
# Structure de la table `stats2`
#
# Liste des jeux de stat brutes demandes sur le serveur

CREATE TABLE IF NOT EXISTS stats2 (
  id int(10) unsigned NOT NULL auto_increment,	# Numro du jeu de stat brut
  mid int(10) unsigned NOT NULL default '0',	# Numro de l`utilisateur
  hostname varchar(255) NOT NULL default '',	# Domaine concern
  folder varchar(255) NOT NULL default '',	# Dossier de stockage des logs
  PRIMARY KEY  (id),
  KEY mid (mid)
) TYPE=MyISAM COMMENT='Statistiques apaches brutes';


#
# Structure de la table `defquotas`
#
# Quotas par dfaut pour les services

CREATE TABLE IF NOT EXISTS defquotas (
  quota varchar(128),				# Nom du quota
  value bigint(20) unsigned default '0',	# Valeur du quota
  type  varchar(128) default 'default',		# Type de compte associe  ce quota
  PRIMARY KEY (quota,type)
) TYPE=MyISAM;

#
# Quotas par defaut pour les nouveaux membres
#
# Ces quotas par defaut sont redefinissables dans l`interface web

INSERT IGNORE INTO defquotas (quota,value) VALUES ('dom',1);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('mail',10);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('ftp',2);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('stats',1);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('mysql',1);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('mysql_users',1);


#
# Structure de la table `forbidden_domains`
#
# Liste des domaines explicitement interdits sur le serveur :

CREATE TABLE IF NOT EXISTS forbidden_domains (
  domain varchar(255) NOT NULL default '',
  PRIMARY KEY  (domain)
) TYPE=MyISAM COMMENT='forbidden domains to install';

#
# Contenu de la table `forbidden_domains`
#

# Registries : 
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
# big isp :
INSERT IGNORE INTO forbidden_domains VALUES ('aol.com');
INSERT IGNORE INTO forbidden_domains VALUES ('hotmail.com');
INSERT IGNORE INTO forbidden_domains VALUES ('microsoft.com');
INSERT IGNORE INTO forbidden_domains VALUES ('sympatico.ca');
INSERT IGNORE INTO forbidden_domains VALUES ('tiscali.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('voila.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('wanadoo.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('yahoo.com');
INSERT IGNORE INTO forbidden_domains VALUES ('yahoo.fr');

#
# Structure de la table `tld`
#
# Liste des tld autoriss sur ce serveur : 

CREATE TABLE IF NOT EXISTS tld (
  tld varchar(128) NOT NULL default '',		# lettres du tld (sans le .)
  mode tinyint(4) NOT NULL default '0',		# Comment est-il autoris ?
  PRIMARY KEY  (tld),
  KEY mode (mode)
) TYPE=MyISAM COMMENT='TLD autoriss et comment sont-ils autoriss ? ';

#
# Contenu de la table `tld`
#

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

--
-- Table structure for table 'variable'
--
-- Taken from http://cvs.drupal.org/viewcvs/drupal/drupal/database/database.mysql?rev=1.164&view=auto
--
-- if comment is null, then the variable is internal and will not show
-- up in the generic configuration panel
CREATE TABLE IF NOT EXISTS variable (
  name varchar(48) NOT NULL default '',
  value longtext NOT NULL,
  comment mediumtext NULL,
  PRIMARY KEY  (name),
  KEY name (name)
) TYPE=MyISAM;

-- hosting_tld: only used, for now, in bureau/admin/adm_*add.php
INSERT IGNORE INTO `variable` (name, value, comment) VALUES ('hosting_tld', 0,
'This is a FQDN that designates the main hostname of the service.

For example, hosting_tld determines in what TLD the "free" user domain
is created. If this is set to "example.com", a checkbox will appear in
the user creation dialog requesting the creator if he wants to create
the domain "username.example.com".

If this is set to 0 or a "false" string, it will be ignored.');

INSERT IGNORE INTO `variable` (name, value, comment) VALUES ('rss_feed', 0,
'This is an RSS feed that will be displayed on the users homepages when
they log in. Set this to 0 or a "false" string to ignore.');

INSERT IGNORE INTO `variable` (name, value, comment) VALUES ('new_email', 0,
'An email will be sent to this address when new accounts are created if set.');

--
-- Table structure for table `dbusers`
--

CREATE TABLE IF NOT EXISTS `dbusers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(10) unsigned NOT NULL default '0',
  `name` varchar(16) NOT NULL default '',
  KEY `id` (`id`)
) TYPE=MyISAM COMMENT='Utilisateurs MySQL des membres';


CREATE TABLE IF NOT EXISTS `mxaccount` (
`login` VARCHAR( 64 ) NOT NULL ,
`pass`  VARCHAR( 64 ) NOT NULL ,
PRIMARY KEY ( `login` )
) COMMENT = 'Allowed account for secondary mx managment';


-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `size_mail` (
  `alias` varchar(255) NOT NULL default '',
  `size` int(10) unsigned NOT NULL default '0',
  `ts` timestamp(14) NOT NULL,
  PRIMARY KEY  (`alias`),
  KEY `ts` (`ts`)
) TYPE=MyISAM COMMENT='Mail space used by pop accounts.';

-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `size_web` (
  `uid` int(10) unsigned NOT NULL default '0',
  `size` int(10) unsigned NOT NULL default '0',
  `ts` timestamp(14) NOT NULL,
  PRIMARY KEY  (`uid`),
  KEY `ts` (`ts`)
) TYPE=MyISAM COMMENT='Web space used by accounts.';

-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `size_db` (
  `db` varchar(255) NOT NULL default '',
  `size` int(10) unsigned NOT NULL default '0',
  `ts` timestamp(14) NOT NULL,
  PRIMARY KEY  (`db`),
  KEY `ts` (`ts`)
) TYPE=MyISAM COMMENT='MySQL Database used space';

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

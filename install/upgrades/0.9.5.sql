-- Pour les durées de comptes
ALTER IGNORE TABLE membres ADD COLUMN created datetime default NULL AFTER type;
ALTER IGNORE TABLE membres ADD COLUMN renewed datetime default NULL AFTER created;
ALTER IGNORE TABLE membres ADD COLUMN duration int(4) default NULL AFTER renewed;

-- Pour l'encryptage des mots de passe ftp
ALTER IGNORE TABLE ftpusers ADD COLUMN encrypted_password VARCHAR(32) default NULL AFTER password;
UPDATE ftpusers SET encrypted_password=ENCRYPT(password) WHERE password!='';

-- Force le bureau https si voulu : 
INSERT INTO variable SET name='force_https', value='0', comment='Shall we force the users to access the managment desktop through HTTPS only ? If this value is true, HTTPS access will be forced. ';

-- --------------------------------------------------------
-- TABLES de mémorisation de la taille des dossiers web/mail/db

CREATE TABLE IF NOT EXISTS `size_db` (
  `db` varchar(255) NOT NULL default '',
  `size` int(10) unsigned NOT NULL default '0',
  `ts` timestamp(14) NOT NULL,
  PRIMARY KEY  (`db`),
  KEY `ts` (`ts`)
) TYPE=MyISAM COMMENT='MySQL Database used space';


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

-- ajout d'une table pour la gestion des utilisateurs mysql
CREATE TABLE `dbusers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(10) unsigned NOT NULL default '0',
  `name` varchar(16) NOT NULL default '',
  KEY `id` (`id`)
) TYPE=MyISAM COMMENT='Utilisateurs MySQL des membres';


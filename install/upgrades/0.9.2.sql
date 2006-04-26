DROP TABLE IF EXISTS `mime`;

CREATE TABLE IF NOT EXISTS `slaveip` (
`ip` VARCHAR( 15 ) NOT NULL ,
`class` TINYINT NOT NULL ,
PRIMARY KEY ( `ip` , `class` )
) COMMENT = 'Allowed ip for slave dns managment';

CREATE TABLE IF NOT EXISTS `slaveaccount` (
`login` VARCHAR( 64 ) NOT NULL ,
`pass`  VARCHAR( 64 ) NOT NULL ,
PRIMARY KEY ( `login` )
) COMMENT = 'Allowed account for slave dns managment';

CREATE TABLE IF NOT EXISTS `mail_alias` (
  `mail` varchar(255) NOT NULL default '',	# Adresse email LOCALE
  `alias` varchar(255) NOT NULL default '',	# WRAPPER 
  PRIMARY KEY  (`mail`)
) TYPE=MyISAM COMMENT='Mail Alias pour postfix';


CREATE TABLE IF NOT EXISTS `mail_users` (
  `uid` int(10) unsigned NOT NULL default '0',	# UID AlternC de l'utilisateur du mail
  `alias` varchar(255) NOT NULL default '',	# Alias = Alias intermédiaire (voir domain)
  `path` varchar(255) NOT NULL default '',	# Chemin vers le mail de l'utilisateur
  `password` varchar(255) NOT NULL default '',	# Mot de passe crypté 
  PRIMARY KEY  (`alias`),
  KEY `path` (`path`),
  KEY `uid` (`uid`)
) TYPE=MyISAM COMMENT='Comptes pop, wrappers, alias';


CREATE TABLE IF NOT EXISTS `mail_domain` (
  `mail` varchar(255) NOT NULL default '',	# Adresse email COMPLETE (login@domaine)
  `alias` text NOT NULL,			# Alias intermédiaire (login_domaine) pour référence dans users
  `uid` int(10) unsigned NOT NULL default '0',	# Numéro de l'utilisateur (alternc)
  `pop` tinyint(4) NOT NULL default '0',	# Est-ce un compte pop ? 
  `type` tinyint(4) NOT NULL default '0',	# Je ne sais plus...
  PRIMARY KEY  (`mail`),
  KEY `uid` (`uid`),
  KEY `pop` (`pop`)
) TYPE=MyISAM COMMENT='Alias en domaine pour Postfix';

ALTER TABLE `membres` CHANGE `enabled` `enabled` TINYINT DEFAULT '1' NOT NULL;

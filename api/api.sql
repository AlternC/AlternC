

-- used by Alternc_Api_Auth_Sharedsecret

CREATE TABLE IF NOT EXISTS `sharedsecret` (
  `uid` int(10) unsigned NOT NULL,
  `secret` varchar(32) NOT NULL,
  PRIMARY KEY (`uid`,`secret`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Shared secrets used by Alternc_Api_Auth_Sharedsecret';


-- used by Alternc_Api_Token

CREATE TABLE IF NOT EXISTS `token` (
  `token` varchar(32) NOT NULL,
  `expire` datetime NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`token`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tokens used by API callers';



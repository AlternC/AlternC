
CREATE TABLE IF NOT EXISTS `mxaccount` (
`login` VARCHAR( 64 ) NOT NULL ,
`pass`  VARCHAR( 64 ) NOT NULL ,
PRIMARY KEY ( `login` )
) COMMENT = 'Allowed account for secondary mx managment';

# On met les quota par defaut du nombre d'utilisateurs mysql
INSERT IGNORE INTO defquotas (quota,value) VALUES ('mysql_users',1);

ALTER TABLE `actions` CHANGE `type` `type` ENUM('CREATE_FILE','FIX_USER','CREATE_DIR','DELETE','MOVE','FIX_DIR','FIX_FILE','CHMOD') DEFAULT NULL ;
-- lower default TTL
ALTER TABLE `domaines` CHANGE `zonettl` `zonettl` int(10) unsigned NOT NULL default '3600';

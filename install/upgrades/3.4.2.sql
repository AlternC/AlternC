

UPDATE domaines_type SET compatibility='' where name='cname';
UPDATE domaines_type SET compatibility=REPLACE(compatibility, 'cname,','');

ALTER TABLE actions 
  CHANGE `type` 
  `type` enum('CREATE_FILE','FIX_USER','CREATE_DIR','DELETE','MOVE','FIX_DIR','FIX_FILE','CHMOD') DEFAULT NULL;


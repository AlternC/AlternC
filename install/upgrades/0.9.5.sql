-- Pour les durées de comptes
ALTER IGNORE TABLE membres ADD COLUMN created datetime default NULL AFTER type;
ALTER IGNORE TABLE membres ADD COLUMN renewed datetime default NULL AFTER created;
ALTER IGNORE TABLE membres ADD COLUMN duration int(4) default NULL AFTER renewed;

-- Pour l'encryptage des mots de passe ftp
ALTER IGNORE TABLE ftpusers ADD COLUMN encrypted_password VARCHAR(32) default NULL AFTER password;
UPDATE ftpusers SET encrypted_password=ENCRYPT(password) WHERE password!='';

-- Force le bureau https si voulu : 
INSERT INTO variable SET name='force_https', value='0', comment='Shall we force the users to access the managment desktop through HTTPS only ? If this value is true, HTTPS access will be forced. ';

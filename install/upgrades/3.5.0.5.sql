-- Set a default value for the https column in the sub_domaines table
-- Support sql mode change beetween 10.1 and 10.3
ALTER TABLE `sub_domaines` MODIFY COLUMN `https` VARCHAR(6) NOT NULL DEFAULT '';
ALTER TABLE `mailbox` MODIFY COLUMN `lastlogin` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `address` MODIFY `type` CHAR(8) NOT NULL DEFAULT '';

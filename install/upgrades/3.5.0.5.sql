-- Set a default value for the https column in the sub_domaines table
ALTER TABLE `sub_domaines` MODIFY COLUMN `https` VARCHAR(6) NOT NULL DEFAULT '';

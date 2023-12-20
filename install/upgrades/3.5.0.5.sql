-- Set a default value for the https column in the sub_domaines table
ALTER TABLE `sub_domaines` MODIFY COLUMN `https` VARCHAR(6) NOT NULL DEFAULT '';

-- Add compatibility with dkim domain type. @see GH#549
UPDATE domaines_type SET compatibility=CONCAT_WS(',', compatibility, 'vhost') WHERE name='dkim' AND compatibility NOT LIKE '%vhost%';
UPDATE domaines_type SET compatibility=CONCAT_WS(',', compatibility, 'dkim') WHERE name IN (SELECT name FROM domaines_type WHERE compatibility like '%txt%' and name != 'dkim') AND compatibility NOT LIKE '%dkim%';


DROP TABLE `certif_alias`;
ALTER TABLE `certificates` DROP `shared`, DROP `ssl_action`, DROP `ssl_result`;

ALTER TABLE `sub_domaines`
      ADD `certificate_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `enable`,
      ADD `provider` VARCHAR(16) NOT NULL DEFAULT '' AFTER `certificate_id`,
      ADD `https` VARCHAR(6) NOT NULL AFTER `provider`; -- SET(http,https,both) (also the suffix of the template name in /etc/alternc/templates/apache2/)

ALTER TABLE `domaines_type`
      ADD `has_https_option` BOOLEAN NOT NULL DEFAULT FALSE AFTER `create_targetdir`;

UPDATE `domaines_type` SET `has_https_option`=1 WHERE name='vhost';

-- Backport old certif_hosts data to sub_domaines
UPDATE `sub_domaines` LEFT JOIN `certif_hosts` ON `sub_domaines`.`id` = `certif_hosts`.`sub` SET `sub_domaines`.`certificate_id` = `certif_hosts`.`certif` WHERE 1;
DROP TABLE `certif_hosts`;

-- Set https status  (http,https,both)
UPDATE `sub_domaines` SET `https` = "https" WHERE `type` LIKE '%-ssl' AND https = '';
UPDATE `sub_domaines` SET `https` = "both" WHERE `type` LIKE '%-mixssl' AND https = '';
UPDATE `sub_domaines` SET `https` = "http" WHERE https = '';
UPDATE `sub_domaines` SET `type` = REPLACE(`type`,'-ssl','');
UPDATE `sub_domaines` SET `type` = REPLACE(`type`,'-mixssl','');
-- Disable https status when domains_type don't provide this
UPDATE `sub_domaines` SET `https` = '' WHERE type IN (select name FROM domaines_type WHERE has_https_option = 0);

-- When two sudomain exists, we consider sub_domains with http and https feature
UPDATE sub_domaines AS sd  INNER JOIN
    (SELECT MIN(id) id FROM `sub_domaines` GROUP BY domaine,sub,type HAVING count(id) > 1) sd1
        ON sd.id = sd1.id
    SET `https` = "both";
-- Delete duplicate lines
DELETE sd1 FROM sub_domaines sd1, sub_domaines sd2 WHERE sd1.id > sd2.id AND sd1.domaine = sd2.domaine AND sd1.sub = sd2.sub AND sd1.type = sd2.type AND sd1.https <> '' AND sd2.https <> '';

-- Regenerate all vhost
UPDATE `sub_domaines` SET `web_action` = 'UPDATE';

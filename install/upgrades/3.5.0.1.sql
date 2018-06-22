
DROP TABLE `certif_alias`;
ALTER TABLE `certificates` DROP `shared`, DROP `ssl_action`, DROP `ssl_result`;

ALTER TABLE `sub_domaines`
      ADD `certificate_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `enable`,
      ADD `provider` VARCHAR(16) NOT NULL DEFAULT '' AFTER `certificate_id`,
      ADD `https` VARCHAR(4) NOT NULL AFTER `provider`; -- SET(http,https,both) (also the suffix of the template name in /etc/alternc/templates/apache2/)

ALTER TABLE `domaines_type`
      ADD `has_https_option` BOOLEAN NOT NULL DEFAULT FALSE AFTER `create_targetdir`; 

UPDATE `domaines_type` SET `has_https_option`=1 WHERE name='vhost';



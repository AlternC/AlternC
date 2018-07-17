
-- migrating DKIM to be inside sub_domaines table
INSERT IGNORE INTO `domaines_type` (name, description, target, entry, compatibility, only_dns, need_dns, advanced, enable) VALUES
('dkim',  'DKIM Key', 'NONE', '%SUB% IN TXT "%TARGET%"', 'txt,defmx,defmx2,mx,mx2,url,ip,ipv6', true, true, true, 'ADMIN');
-- migrating AUTODISCOVER / AUTOCONF to be inside sub_domaines table
INSERT IGNORE INTO `domaines_type` (name, description, target, entry, compatibility, only_dns, need_dns, advanced, enable) VALUES
('autodiscover',  'Email autoconfiguration', 'NONE', '%SUB% IN A @@PUBLIC_IP@@', 'txt,defmx,defmx2,mx,mx2', false, true, true, 'ADMIN');


-- upgrade from 3.4.10 and 3.4.11 (a bug prevented them to be inserted :/ )
ALTER TABLE mailbox MODIFY  `lastlogin` DATETIME NOT NULL DEFAULT 0;
ALTER TABLE mailbox ADD  `lastloginsasl` DATETIME NOT NULL DEFAULT 0 AFTER `lastlogin`;
ALTER TABLE `domaines` MODIFY `zonettl` INT(10) UNSIGNED NOT NULL default '3600';

-- upgrade to better hashes ($6$, 20000 loops) in membres and ftpusers
ALTER TABLE `membres` MODIFY `pass` VARCHAR(255);
ALTER TABLE `ftpusers` MODIFY `encrypted_password` VARCHAR(255);

-- upgrade to merge alternc-ssl into alternc + change the way we work on SSL
DROP TABLE IF EXISTS `certif_alias`;

ALTER TABLE `certificates`
      DROP `shared`,
      DROP `ssl_action`,
      DROP `ssl_result`,
      ADD `provider` VARCHAR(16) NOT NULL DEFAULT '',
      ADD `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP;
  
ALTER TABLE `sub_domaines`
      ADD `certificate_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `enable`,
      ADD `provider` VARCHAR(16) NOT NULL DEFAULT '' AFTER `certificate_id`,
      ADD `https` VARCHAR(6) NOT NULL AFTER `provider`; -- SET(http,https,both) (also the suffix of the template name in /etc/alternc/templates/apache2/)

ALTER TABLE `domaines_type`
      ADD `has_https_option` BOOLEAN NOT NULL DEFAULT FALSE AFTER `create_targetdir`;

UPDATE `domaines_type` SET `has_https_option`=1 WHERE name='vhost';

-- Backport old certif_hosts data to sub_domaines
UPDATE `sub_domaines` LEFT JOIN `certif_hosts` ON `sub_domaines`.`id` = `certif_hosts`.`sub`
       SET `sub_domaines`.`certificate_id` = `certif_hosts`.`certif`;
DROP TABLE IF EXISTS `certif_hosts`;

-- Set https status  (http,https,both)
UPDATE `sub_domaines` SET `https` = "https" WHERE `type` LIKE '%-ssl' AND https = '';
UPDATE `sub_domaines` SET `https` = "both" WHERE `type` LIKE '%-mixssl' AND https = '';
UPDATE `sub_domaines` SET `https` = "http" WHERE https = '';
UPDATE `sub_domaines` SET `type` = REPLACE(`type`,'-ssl','');
UPDATE `sub_domaines` SET `type` = REPLACE(`type`,'-mixssl','');
-- Disable https status when domains_type don't use it
UPDATE `sub_domaines` SET `https` = '' WHERE type IN (SELECT name FROM domaines_type WHERE has_https_option = 0);

-- When two subdomain exists, we consider sub_domains with http and https feature
UPDATE sub_domaines AS sd  INNER JOIN
    (SELECT MIN(id) id FROM `sub_domaines` GROUP BY domaine,sub,type HAVING count(id) > 1) sd1
        ON sd.id = sd1.id
    SET `https` = "both";
-- Delete duplicate lines
DELETE sd1 FROM sub_domaines sd1, sub_domaines sd2
    WHERE sd1.id > sd2.id AND sd1.domaine = sd2.domaine AND sd1.sub = sd2.sub AND sd1.type = sd2.type
    AND sd1.https <> '' AND sd2.https <> '';

-- we need to regenerate all vhost, they will be by AlternC.install
-- UPDATE `sub_domaines` SET `web_action` = 'UPDATE';

-- change some variable names :

UPDATE variable
   SET name="fqdn_dovecot",comment="FQDN name for humans for pop/imap services. If you change it, launch reload-certs"
      WHERE name="mail_human_imap";

UPDATE variable
   SET name="fqdn_postfix",comment="FQDN name for humans for smtp services. If you change it, launch reload-certs"
      WHERE name="mail_human_smtp";

UPDATE variable
   SET name="fqdn_proftpd",comment="FQDN name for humans for ftp services. If you change it, launch reload-certs"
      WHERE name="ftp_human_name";

DELETE FROM variable WHERE name IN (
  'mail_human_imaps','mail_human_pop3','mail_human_pop3s',
  'mail_human_smtps','mail_human_submission', 'mail_human_imap', 'mail_human_smtp',
  'ftp_human_name'
  );

-- we'd like to prepare for IPv6 ;) 
ALTER TABLE  `domaines_type` CHANGE  `entry`  `entry` TEXT DEFAULT '';


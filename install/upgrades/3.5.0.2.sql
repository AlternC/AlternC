-- upgrade from 3.5.0.1 to 3.5.0.2
INSERT IGNORE INTO `domaines_type` (name, description, target, entry,                             compatibility,                               only_dns, need_dns, advanced, enable, has_https_option) values
('vhost-http','Locally hosted with http->https',   'DIRECTORY', '%SUB% IN A @@PUBLIC_IP@@',      'txt,defmx,defmx2,mx,mx2',                   false,    false,    false, 'NONE', false),
('vhost-https','Locally hosted with http->https',   'DIRECTORY', '%SUB% IN A @@PUBLIC_IP@@',      'txt,defmx,defmx2,mx,mx2',                   false,    false,    false, 'NONE', false),
('vhost-both', 'Locally hosted with http and https', 'DIRECTORY', '%SUB% IN A @@PUBLIC_IP@@',     'txt,defmx,defmx2,mx,mx2',                   false,    false,    false, 'NONE', false);


-- Remove duplicate autodiscover/autoconfig (run after php script)
-- Provide idenpotent update process
UPDATE sub_domaines SET web_action="DELETE" WHERE sub="autoconfig" AND type="autodiscover" AND id NOT IN (SELECT MIN(id) FROM sub_domaines WHERE sub="autoconfig" AND type="autodiscover" GROUP BY domaine);
UPDATE sub_domaines SET web_action="DELETE" WHERE sub="autodiscover" AND type="autodiscover" AND id NOT IN (SELECT MIN(id) FROM sub_domaines WHERE sub="autodiscover" AND type="autodiscover" GROUP BY domaine);
UPDATE sub_domaines SET web_action="DELETE" WHERE sub="@" AND type="autodiscover";
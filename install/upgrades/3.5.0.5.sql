-- Add compatibility with dkim domain type. @see GH#549
UPDATE domaines_type SET compatibility=CONCAT_WS(',', compatibility, 'vhost') WHERE name='dkim' AND compatibility NOT LIKE '%vhost%';
UPDATE domaines_type SET compatibility=CONCAT_WS(',', compatibility, 'dkim') WHERE name IN ('autodiscover', 'defmx', 'defmx2', 'ip', 'ipv6', 'mx', 'mx2', 'panel', 'txt', 'url', 'vhost', 'vhost-both', 'vhost-http', 'vhost-https') AND compatibility NOT LIKE '%dkim%';

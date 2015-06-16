

UPDATE domaines_type SET compatibility='' where name='cname';
UPDATE domaines_type SET compatibility=REPLACE(compatibility, 'cname,','');


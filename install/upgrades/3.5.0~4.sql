-- Add compatibility between url domain type and MX records. @see GH#428
UPDATE domaines_type SET compatibility=CONCAT_WS(',', compatibility, 'mx') WHERE name='url' AND compatibility NOT LIKE '%,mx%' AND compatibility NOT LIKE 'mx,%';
UPDATE domaines_type SET compatibility=CONCAT_WS(',', compatibility, 'mx2') WHERE name='url' AND compatibility NOT LIKE '%,mx2%' AND compatibility NOT LIKE 'mx2,%';

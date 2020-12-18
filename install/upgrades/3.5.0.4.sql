-- Add compatibility between url domain type and MX records. @see GH#428
UPDATE domaines_type SET compatibility = 'txt,defmx,defmx2,mx,mx2' WHERE name = 'url';

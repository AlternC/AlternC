-- Increase size of sub_domaines valeur column. @see GH#337
ALTER TABLE sub_domaines MODIFY COLUMN valeur VARCHAR(1024);
DROP INDEX IF EXISTS compte on `sub_domaines`;

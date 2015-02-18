
UPDATE sub_domaines SET valeur=CONCAT('/',valeur), web_action="UPDATE"
       WHERE  type IN ('vhost', 'php52')  AND valeur NOT LIKE '/%';


/* Affect alternc selector to each DKIM key */
UPDATE IGNORE sub_domaines SET sub='alternc._domainkey', web_action='UPDATE' WHERE type="dkim" AND sub="@" AND enable="ENABLED";
UPDATE IGNORE sub_domaines SET sub='alternc._domainkey', web_action='UPDATE' WHERE type="dkim" AND sub="" AND enable="ENABLED";
UPDATE IGNORE sub_domaines SET sub='alternc._domainkey', web_action='UPDATE' WHERE type="txt"  AND sub="" AND valeur LIKE "%v=DKIM1%" AND enable="ENABLED";

/* Clear invalid selector */
UPDATE IGNORE sub_domaines SET web_action='DELETE' WHERE sub="@" AND type="dkim" AND enable="ENABLED";
UPDATE IGNORE sub_domaines SET web_action='DELETE' WHERE type="txt"  AND sub="" AND valeur LIKE "%v=DKIM1%" AND enable="ENABLED";
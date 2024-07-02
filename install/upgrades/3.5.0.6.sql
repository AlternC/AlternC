/* Affect alternc selector to each DKIM key */
UPDATE IGNORE sub_domaines SET sub='alternc._domainkey', web_action='UPDATE' WHERE type="dkim" AND sub="@" AND enable="ENABLED";
UPDATE IGNORE sub_domaines SET sub='alternc._domainkey', web_action='UPDATE' WHERE type="dkim" AND sub="" AND enable="ENABLED";
UPDATE IGNORE sub_domaines SET sub='alternc._domainkey', web_action='UPDATE' WHERE type="txt"  AND sub="" AND valeur LIKE "%v=DKIM1%" AND enable="ENABLED";

/* Clear invalid selector */
UPDATE IGNORE sub_domaines SET web_action='DELETE' WHERE sub="@" AND type="dkim" AND enable="ENABLED";
UPDATE IGNORE sub_domaines SET web_action='DELETE' WHERE type="txt"  AND sub="" AND valeur LIKE "%v=DKIM1%" AND enable="ENABLED";

/* Remove duplicate dkim rules */
UPDATE sub_domaines SET web_action="DELETE" WHERE sub="alternc._domainkey" AND type="dkim" AND id NOT IN (SELECT MIN(id) FROM sub_domaines WHERE sub="alternc._domainkey" AND type="dkim" GROUP BY domaine);

/* Request a global delete about all action waiting */
UPDATE domaines SET dns_action='UPDATE' WHERE domaine IN (SELECT domaine FROM sub_domaines WHERE web_action='DELETE');
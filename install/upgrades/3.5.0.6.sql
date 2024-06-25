UDPATE IGNORE sub_domaines SET sub='alternc._domainkey', web_action='UPDATE' WHERE type="dkim" AND sub="@" AND enable="ENABLED";
UDPATE IGNORE sub_domaines SET sub='alternc._domainkey', web_action='UPDATE' WHERE type="dkim" AND sub="" AND enable="ENABLED";
UDPATE IGNORE sub_domaines SET sub='alternc._domainkey', web_action='UPDATE' WHERE type="txt"  AND sub="" AND valeur LIKE "%v=DKIM1%" AND enable="ENABLED";

-- some sub_domaines entries are not tagged with the right account's UID :
UPDATE sub_domaines,domaines SET sub_domaines.compte = domaines.compte WHERE sub_domaines.domaine = domaines.domaine ;

alter table sub_domaines drop index compte;
alter table sub_domaines add UNIQUE (compte,domaine,sub,type,valeur,web_action);


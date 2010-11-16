-- Alter table to allow use of ipv6, cname and txt in dns record
ALTER TABLE sub_domaines_standby DROP PRIMARY KEY;
ALTER TABLE sub_domaines_standby ADD CONSTRAINT pk_SubDomainesStandby  PRIMARY KEY  (compte,domaine,sub,action,type);

-- Alter table to allow use of ipv6, cname and txt in dns record
ALTER TABLE sub_domaines DROP PRIMARY KEY;
ALTER TABLE sub_domaines ADD CONSTRAINT pk_SubDomaines PRIMARY KEY (compte,domaine,sub,type);

-- Ajout du champ 'type' pour les membres
ALTER TABLE membres ADD COLUMN type varchar(128) default 'default' AFTER admlist;

-- Ajout du champ 'type' pour les quotas par défaut
ALTER TABLE defquotas ADD COLUMN type varchar(128) default 'default' AFTER value;

-- we don't store cleartext passwords anymore, we use saslauthd
ALTER TABLE `mail_users` DROP `sasl`;

-- We don't use "used" quota anymore.
ALTER TABLE `quotas` DROP `used`; 

-- Add a primary key to 'defquotas'
ALTER TABLE `defquotas` ADD PRIMARY KEY (`quota`,`type`);

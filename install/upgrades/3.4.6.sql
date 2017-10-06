
ALTER TABLE `actions` ENGINE InnoDB;
ALTER TABLE `address` ENGINE InnoDB;
ALTER TABLE `alternc_status` ENGINE InnoDB;
ALTER TABLE `authorised_ip` ENGINE InnoDB;
ALTER TABLE `authorised_ip_affected` ENGINE InnoDB;
ALTER TABLE `browser` ENGINE InnoDB;
ALTER TABLE `chgmail` ENGINE InnoDB;
ALTER TABLE `cron` ENGINE InnoDB;
ALTER TABLE `db` ENGINE InnoDB;
ALTER TABLE `db_servers` ENGINE InnoDB;
ALTER TABLE `dbusers` ENGINE InnoDB;
ALTER TABLE `default_subdomains` ENGINE InnoDB;
ALTER TABLE `defquotas` ENGINE InnoDB;
ALTER TABLE `domaines` ENGINE InnoDB;
ALTER TABLE `domaines_type` ENGINE InnoDB;
ALTER TABLE `forbidden_domains` ENGINE InnoDB;
ALTER TABLE `ftpusers` ENGINE InnoDB;
ALTER TABLE `local` ENGINE InnoDB;
ALTER TABLE `mailbox` ENGINE InnoDB;
ALTER TABLE `membres` ENGINE InnoDB;
ALTER TABLE `mxaccount` ENGINE InnoDB;
ALTER TABLE `piwik_sites` ENGINE InnoDB;
ALTER TABLE `piwik_users` ENGINE InnoDB;
ALTER TABLE `policy` ENGINE InnoDB;
ALTER TABLE `quotas` ENGINE InnoDB;
ALTER TABLE `recipient` ENGINE InnoDB;
ALTER TABLE `sessions` ENGINE InnoDB;
ALTER TABLE `size_db` ENGINE InnoDB;
ALTER TABLE `size_mailman` ENGINE InnoDB;
ALTER TABLE `size_web` ENGINE InnoDB;
ALTER TABLE `slaveaccount` ENGINE InnoDB;
ALTER TABLE `slaveip` ENGINE InnoDB;
ALTER TABLE `sub_domaines` ENGINE InnoDB;
ALTER TABLE `tld` ENGINE InnoDB;
ALTER TABLE `variable` ENGINE InnoDB;
ALTER TABLE `vm_history` ENGINE InnoDB;

-- If the default_subdomains table already exists, prevent the following INSERT INTO to double the entries
ALTER TABLE `default_subdomains` ADD UNIQUE KEY `unique_row` (`sub`,`domain_type`,`domain_type_parameter`,`concerned`);


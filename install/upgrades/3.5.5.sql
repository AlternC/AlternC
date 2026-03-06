
CREATE TABLE `mail_webhooks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `address_id` bigint(20) unsigned NOT NULL REFERENCES `address`(`id`) ON DELETE CASCADE, -- Reference to address.
  `url` varchar(255) NOT NULL
  PRIMARY KEY(`address_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT = 'Table containing webhooks URLs for mail addresses';

CREATE OR REPLACE VIEW `alias_view` AS
select concat(`address`.`address`,'@',`domaines`.`domaine`) AS `mail`,
concat(if(isnull(`mailbox`.`id`),'',concat(concat(`address`.`address`,'@',`domaines`.`domaine`),'\n')),`recipient`.`recipients`) AS `alias`
from (((`recipient` join `address` on((`address`.`id` = `recipient`.`address_id`)))
left join `mailbox` on((`mailbox`.`address_id` = `address`.`id`)))
join `domaines` on((`domaines`.`id` = `address`.`domain_id`)))
where `address`.`enabled` = 1
union
select distinct concat(`m`.`login`,'@',`v`.`value`) AS `mail`,
`m`.`mail` AS `alias`
from ((`membres` `m` join `variable` `v`) join `domaines` `d`)
where (`v`.`name` = 'mailname_bounce')
union
select distinct concat('alterncpanel','@',`v`.`value`) AS `mail`,
`m`.`mail` AS `alias`
from ((`membres` `m` join `variable` `v`) join `domaines` `d`)
where (`v`.`name` = 'mailname_bounce' AND `m`.`uid`=2000);


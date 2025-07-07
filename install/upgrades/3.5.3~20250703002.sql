alter table dbusers change name name varchar(80) not null default '';
alter table dbusers change password password varchar(80) null default null;
alter table db change login login varchar(80) not null default '';
alter table db change pass pass varchar(80) not null default '';
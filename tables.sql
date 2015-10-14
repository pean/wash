
CREATE DATABASE wa CHARACTER SET utf8 COLLATE utf8_unicode_ci;

create table wash_users (
id int not null auto_increment,
name varchar(255),
token char(32),
primary key(id),
unique(token)
)engine=InnoDB;


create table wash_urls(
id int not null auto_increment,
user_id int,
url text,
clicks int default 0,
created datetime,
primary key(id),
foreign key uid(user_id) references wash_users(id) on delete cascade
)engine=InnoDB;


create table wash_aliases(
url_id int,
alias varchar(255) unique,
foreign key uid(url_id) references wash_urls(id) on delete cascade
)engine=InnoDB;
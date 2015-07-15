#!/usr/bin/php -q
<?php

require_once("./0-config.php");


$table_query = "
    CREATE  IF NOT EXISTS TABLE `tmp_mail_pass` (
	`id` INT NOT NULL AUTO_INCREMENT ,
	`user` VARCHAR(255) NULL ,
	`pass` VARCHAR(255) NULL ,
	PRIMARY KEY (`id`) );
";

$connection = mysql_query($table_query ) ;


$fp = fopen('/dev/urandom', 'r');
$salt = fread($fp, 32);
fclose($fp);

$query = '
    insert into tmp_mail_pass (user,pass) 
	select user, lower(substring(password(concat(user,"'.$salt.'")), 2, 10)) as pass 
	from dovecot_view 
	where `password` IS NOT NULL;
';


$connection = mysql_query($query);


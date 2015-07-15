#!/usr/bin/php -q
<?php

require_once("./0-config.php");

$table_query = "
    DROP TABLE IF EXISTS`tmp_mail_pass` 
";

$connection = mysql_query($table_query ) ;


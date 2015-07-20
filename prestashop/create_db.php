#!/usr/bin/php -q
<?php
	//Set database name
	$db_name = "ps";

	//Get Alternc User to manage
	$args = getopt("u:");

	if (!isset($args["u"])) {
		echo "Uid missing\n";
		exit(1);
	}


	$uid = (int)$args["u"];

	if ( empty($uid) OR $uid < 1000) {
		echo "Invalid UID\n";
		exit(1);
	}

	if(!chdir("/var/alternc/bureau"))
		exit(1);

	require("/var/alternc/bureau/class/config_nochk.php");


	if(!function_exists('mysql_connect'))  {
  		if(!dl("mysql.so"))
		    exit(1);
	}

	// We go root
	$admin->enabled=1;

	//Get user to override
	$mem->su($uid);

	//Create database
	$res = $mysql->add_db($db_name);

	if (!$res) {
		echo "error in database creation\n";
		exit(1);
	}
	$mysql->grant("ps",$mem->user['login']."_".$db_name);

	//Get user password
	$res = $db->query("SELECT password FROM dbusers WHERE name LIKE '".$mem->user['login']."_".$db_name."' AND uid = ".$uid.";");
	$db->next_record();
        $password = $db->f("password");

	//Free account
	$mem->unsu();

	//Return result
	echo $db_name." ".$mem->user['login']."_".$db_name." ".$password."\n";
	exit(0);

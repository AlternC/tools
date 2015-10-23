#!/usr/bin/php
<?php

   /* Fix the $uid_myadm mysql users access.
      This script is idempotent and can be launch anytime 
      usually after an AlternC upgrade
    */

   /* ATTENTION : This script only works id db_server IS LOCALHOST !!! */
   /* FIXME make it work in any case, using 2 mysql connections ! */

$f=fopen("/etc/alternc/my.cnf","rb");
while ($s=fgets($f,1024)) {
  if (preg_match('#database="([^"]*)#',$s,$mat)) {
    $mdb=$mat[1];
  }
  if (preg_match('#host="([^"]*)#',$s,$mat)) {
    $mhost=$mat[1];
  }
  if (preg_match('#user="([^"]*)#',$s,$mat)) {
    $muser=$mat[1];
  }
  if (preg_match('#password="([^"]*)#',$s,$mat)) {
    $mpass=$mat[1];
  }
}
fclose($f);


function create_pass($length = 8){
  $chars = "1234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
  $i = 0;
  $password = "";
  while ($i <= $length) {
    $password .= @$chars{mt_rand(0,strlen($chars))};
    $i++;
  }
  return $password;
}


mysql_connect($mhost,$muser,$mpass);
mysql_select_db($mdb);

mysql_query("UPDATE dbusers SET enable='ACTIVATED' WHERE name!=CONCAT(uid,'_myadm');");

$r=mysql_query("SELECT uid, login FROM membres;");
while ($c=mysql_fetch_array($r)) {
  $membres[$c["uid"]]=$c["login"];
}

foreach($membres as $uid => $membre) {
  $ok=@mysql_fetch_array(mysql_query("SELECT * FROM dbusers WHERE uid=$uid AND NAME='".$uid."_myadm';"));
  if (!$ok) {
    echo "Creating user ".$uid."_myadm for login ".$membre."\n";
    $pass=create_pass(8);
    mysql_query("INSERT INTO dbusers SET uid=$uid, name='".$uid."_myadm', password='$pass', enable='ADMIN';");
    echo mysql_error();
  } else {
    $pass=$ok["password"];
  }
  echo "Granting rights to user ".$uid."_myadm for login ".$membre." ... ";

  // Now granting him access to all user's databases
  mysql_query("GRANT USAGE ON *.* TO '".$uid."_myadm'@localhost IDENTIFIED BY '$pass';");
  echo mysql_error();
  $t=mysql_query("SELECT * FROM db WHERE uid=$uid;");
  echo mysql_error();
  while ($d=mysql_fetch_array($t)) {
    mysql_query("GRANT ALL ON ".$d["db"].".* TO '".$uid."_myadm'@localhost;");
    echo " ".$d["db"];
    echo mysql_error();
  }
  echo "\n";

}


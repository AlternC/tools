#!/usr/bin/php
<?php
// update the path for the temporary files. Script for drupal on alternc 3.2.x only


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

$ALTERNC_ROOT="/var/alternc/html";
$f=fopen("/etc/alternc/my.cnf","rb");
while ($s=fgets($f,1024)) {
  if (preg_match('#ALTERNC_HTML="([^"]*)#',$s,$mat)) {
    $ALTERNC_ROOT=$mat[1];
  }
}
fclose($f);
$ALTERNC_ROOT=rtrim($ALTERNC_ROOT,"/");



mysql_connect($mhost,$muser,$mpass);
mysql_select_db($mdb);

$drupaltables=array("variable","role","users","node");

$dbs=array();
$r=mysql_query("SHOW DATABASES");
while ($c=mysql_fetch_array($r)) {
  $dbs[]=$c["Database"];
}

foreach($dbs as $db) {
  $alternc=preg_replace("#_.*#","",$db);
  mysql_select_db($db);
  $drupalfound=0;
  $r=mysql_query("SHOW TABLES");
  while ($c=mysql_fetch_array($r)) {
    if (in_array($c[0],$drupaltables)) {
      $drupalfound++;
    }
  }
  if ($drupalfound==count($drupaltables)) {
    // c'est un drupal ;) 
    echo "db:$db\n";
    $sql="UPDATE variable SET value='".addslashes(serialize($ALTERNC_ROOT."/".substr($alternc,0,1)."/".$alternc."/tmp"))."' WHERE name='file_temporary_path';";
    mysql_query($sql);
    if (mysql_errno()) {
      echo "ERR:".mysql_error()."\n";
    }
    //    echo $sql."\n";
  }
}


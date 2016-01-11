#!/usr/bin/php
<?php

$db_password = "%insertYourPassword%";
if( "%insertYourPassword%" === $db_password ){
	die("[!] Please provide the system password");
}
mysql_connect('localhost','sysusr',$db_password);
mysql_select_db('roundcube');

$email=$argv[1];
$filename=$email.".abook";

$user_id=0;

$r=mysql_query("SELECT user_id FROM users WHERE username='$email'");
if ($r) {
  if ($row=mysql_fetch_row($r)) {
    $user_id=$row[0];
  }
}

if (!$user_id) {
  exit();
} else {
  echo "Processing ".$email." - user_id=".$user_id."\n";
}

$fh = fopen($filename, "rb");
if ($fh === false) {
    echo "Unable open file";
}
while (($line = fgets($fh)) !== false) {
  // process the line read.
  $contact_id=0;

  list($CONTACT_PRENOMNOM, $CONTACT_PRENOM, $CONTACT_NOM, $CONTACT_EMAIL, $CONTACT_WORDS) = explode("|", addslashes($line), 5);
  $r=mysql_query("SELECT contact_id FROM contacts WHERE user_id=$user_id AND email='$CONTACT_EMAIL'");
  if ($r) {
    if ($row=mysql_fetch_row($r)) {
      $contact_id=$row[0];
    }
  }

  if (!$CONTACT_PRENOMNOM) {
    if (!$CONTACT_NOM) {
      $CONTACT_PRENOMNOM=$CONTACT_EMAIL;
    } else {
      $CONTACT_PRENOMNOM=$CONTACT_NOM." ".$CONTACT_PRENOM;
    }
  }

  $CONTACT_VCARD=addslashes("
BEGIN:VCARD
VERSION:3.0
N:".$CONTACT_NOM.";".$CONTACT_PRENOM."
FN:".$CONTACT_PRENOMNOM."
EMAIL;TYPE=PREF,INTERNET:".$CONTACT_EMAIL."
NOTE:".$CONTACT_WORDS."END:VCARD
");



  $sql="user_id=$user_id, name='".$CONTACT_PRENOMNOM."',firstname='".$CONTACT_PRENOM."', surname='".$CONTACT_NOM."', email='".$CONTACT_EMAIL."',words='".$CONTACT_WORDS."', vcard='".$CONTACT_VCARD."' ";

  if ($contact_id==0) {

    //echo ("N'existe pas : $CONTACT_NOM $CONTACT_PRENOM $CONTACT_EMAIL\n");
    $QUERY="INSERT INTO contacts SET ".$sql;

  } else {
    
    //echo ("Existe : $CONTACT_NOM $CONTACT_PRENOM $CONTACT_EMAIL\n");
    $QUERY="UPDATE contacts SET ".$sql." WHERE contact_id=$CONTACT_ID";
   
  }
  mysql_query($QUERY."\n");


}

echo "done"
?>

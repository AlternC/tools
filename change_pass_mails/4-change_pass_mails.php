#!/usr/bin/php -q
<?php

require_once("./0-config.php");


$dir_mail = APP_PATH."/mails";
if( ! is_dir($dir_mail ) ){
    mkdir( $dir_mail, 0700, true);
}

$dir_cmd = APP_PATH."/cmd";
if( ! is_dir($dir_cmd) ){
    mkdir( $dir_cmd, 0700, true);
}

$mail_template = APP_PATH."/templates/mail_template.php";
$cmd_template = APP_PATH."/templates/cmd_template.php";

$query = 'select a.id, concat(address,"@",domaine) as email from mailbox m  join address a on m.address_id = a.id join domaines d on d.id = domain_id;';

$connection = mysql_query($query);

$emailList = array();

while ($result = mysql_fetch_array($connection)) {
        $emailList[$result["email"]] = $result["id"];
}


$query = 'select user as email,pass as password from tmp_mail_pass;';
$connection = mysql_query($query);

while ($result = mysql_fetch_array($connection)) {
$password= _md5cr($result["password"]);
$email = $result["email"];
$id = $emailList[$email];

echo "$email : $password : $id\n";

$update_query = "update address set `password`= '".$password."' where id=".$id;
$update_connexion = mysql_query($update_query);

}

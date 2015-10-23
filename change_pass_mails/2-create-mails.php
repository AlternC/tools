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

$query = 'select user as email,pass as password from tmp_mail_pass;';

$connection = mysql_query($query);

while ($result = mysql_fetch_array($connection)) {

    $password= $result["password"];
    $email = $result["email"];

    ob_start();
    include($mail_template);
    $out = ob_get_contents();
    file_put_contents( $dir_mail."/$email.html",$out );
    ob_end_clean();
    ob_start();
    include($cmd_template);
    $out = ob_get_contents();
    file_put_contents( $dir_cmd."/$email.sh",$out );
    ob_end_clean();


}


<?php

require_once("./0-config.php");
$login=$argv[1];
$membre_mail=$argv[2];
$file = APP_PATH."/mails/$login.html";
if( ! is_readable( $file)){
    echo "Impossible de lire le fichier $file";
    exit( 1 );
}
$content = base64_encode(file_get_contents($file));
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/plain; charset=UTF-8' . "\r\n";
$headers .= "Content-Transfer-Encoding: base64\r\n";
$headers .= "From: Support Octopuce <support@octopuce.fr>\r\n";
$headers .= "Reply-To: Support Octopuce <support@octopuce.fr>\r\n";
$headers .= "Return-Path: Support Octopuce <support@octopuce.fr>\r\n";
if( ! mail($membre_mail,"[IMPORTANT] Changement de mot de passe pour votre compte email",$content,$headers) ){
    echo "Erreur dans l'envoi de mail";
    exit( 1 );
}

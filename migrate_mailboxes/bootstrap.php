<?php

$pathList = array_merge(array("."), explode(PATH_SEPARATOR, get_include_path()));
set_include_path(implode(PATH_SEPARATOR, $pathList));
require_once('AutoLoader.php');

if (getenv("TEST")) {

    require_once 'test-bootstrap.php';
} else {
    // Alternc bootstrap
    require_once("/usr/share/alternc/panel/class/config_nochk.php");
}

define("APP_PATH", __DIR__);
chdir(APP_PATH);

// Create a logger
$logger = new Logger\FileLogger(array(
    "logDir" => "/tmp",
    "filenameMask" => "alternc.mailboxes_export.log"
        ));


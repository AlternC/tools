<?php

// Alternc bootstrap
require_once("/usr/share/alternc/panel/class/config_nochk.php");

define("APP_PATH",__DIR__);
chdir( APP_PATH );


/**
 * Attempts to load a class in multiple path, the PSR-0 or old style way
 * 
 * @staticvar array $srcPathList
 * @staticvar boolean $init
 * @param string $class_name
 * @return boolean
 */
function __autoload($class_name)
{
    // Contains (Namespace) => directory
    static $srcPathList                 = array();
    static $init;
    
    // Attempts to set include path and directories once
    if( is_null( $init )){
        
        // Sets init flag
        $init                           = true;
        
        // Sets a contextual directory
        $srcPathList["standard"]        = APP_PATH."/lib";

        // Updates include_path according to this list
        $includePathList                = explode(PATH_SEPARATOR, get_include_path()); 

        foreach($srcPathList as $path){
            if ( !in_array($path, $includePathList)){
                $includePathList[]      = $path;
            }
        }
        // Reverses the path for search efficiency
        $finalIncludePathList           = array_reverse($includePathList);
        
        // Sets the updated include_path
        set_include_path(implode(PATH_SEPARATOR, $finalIncludePathList));
        
    }
    
    // Accepts old Foo_Bar namespacing
    if(preg_match("/_/", $class_name)){
        $file_name                      = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
        
    // Accepts 5.3 Foo\Bar PSR-0 namespacing 
    } else if(preg_match("/\\\/", $class_name)){
        $file_name                      = str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class_name,'\\')) . '.php';
        
    // Accepts non namespaced classes
    } else {
        $file_name                      = $class_name . '.php';        
    }
   
    // Attempts to find file in namespace
    foreach($srcPathList as $namespace => $path ){
        $file_path                      = $path.DIRECTORY_SEPARATOR.$file_name;
        if(is_file($file_path) && is_readable($file_path)){
            require $file_path;
            return true;
        }
    }
    
    // Failed to find file
    return false;
}

// Create a logger
$logger = new Logger\FileLogger(array(
    "logDir" => "/tmp",
    "filenameMask" => "alternc.mailboxes_export.log"
));


<?php
/**
 * @since 2015-10-15
 * @author Alban
 * @license GPL v2
 */

// Load bootstrap for autoload and logger
set_include_path( ".:".get_include_path() );
require_once("bootstrap.php");

// Instanciate export service
$service = new Alternc_Tools_Domains_Import(array(
    "db" => $db
));

// Instanciate command line parser
$consoleParser = new Console_CommandLine(array(
    "description" => "Imports Alternc domains from a file for import and gets ready for sync."
));

// Configure command line parser
$consoleParser->add_version_option = false;
$consoleParser->addOption("input_file", array(
    "help_name" => "/tmp/out.json",
    "short_name" => "-i",
    "long_name" => "--input-file",
    "description" => "Input file name and path",
    'default'     => $service->default_input
));
$consoleParser->addOption("restrict_input_file", array(
    "help_name" => "/tmp/missing.txt",
    "short_name" => "-r",
    "long_name" => "--restrict-file",
    "description" => "Domain list, one per line. Others won't be imported.",
    'default'     => ""
));
$consoleParser->addOption("force_uid", array(
    "help_name" => "2001",
    "short_name" => "-u",
    "long_name" => "--force-uid",
    "description" => "Force the domain owner",
    'default'     => null 
));

// Run the command line parser
try {
    $commandLineResult                  = $consoleParser->parse();
    // Run the service
    if( ! ($result = $service->import($commandLineResult ))){
        throw new \Exception("Import process failed");
    }
    
    $message = "";
    $level = Logger\AbstractLogger::INFO;
    if( isset( $result["message"])){
        $message .= $result["message"]."\n";
    }
    if( isset( $result["successList"])  &&  count( $result["successList"])){
        $message .= "# Success\n";
        $message .= implode("\n", $result["successList"])."\n";
    }
    if( isset( $result["errorsList"]) && count( $result["errorsList"])){
        $level = Logger\AbstractLogger::WARNING;
        $message .= "# Errors \n";
        $message .= implode("\n", $result["errorsList"])."\n";
    }
    echo($message."\n");
    $logger->logMessage($level,$message);

// Boom goes your request
} catch (\Exception $exc) {
    $message = $exc->getMessage();
    $logger->logMessage(Logger\AbstractLogger::CRITICAL,$message);
    $consoleParser->displayError($message);
}


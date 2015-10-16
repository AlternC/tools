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
$service = new Alternc_Tools_Mailbox_Import(array(
    "db" => $db
));

// Instanciate command line parser
$consoleParser = new Console_CommandLine(array(
    "description" => "Imports Alternc mailboxes from a file for import and gets ready for sync."
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
$consoleParser->addOption("output_file", array(
    "help_name" => "/tmp/out.json",
    "short_name" => "-o",
    "long_name" => "--output-file",
    "description" => "Export file name and path",
    'default'     => $service->default_output 
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
    if( count( $result["errors"])){
        $message .= $result["message"]."\n";
    }
    if( count( $result["success"])){
        $message .= "# Success\n";
        $message .= implode("\n", $result["success"])."\n";
    }
    if( count( $result["errors"])){
        $level = Logger\AbstractLogger::WARNING;
        $message .= "# Errors \n";
        $message .= implode("\n", $result["success"])."\n";
    }
    echo($message."\n");
    $logger->logMessage($level,$message);

// Boom goes your request
} catch (\Exception $exc) {
    $message = $exc->getMessage();
    $logger->logMessage(Logger\AbstractLogger::CRITICAL,$message);
    $consoleParser->displayError($message);
}


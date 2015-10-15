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
$service = new Alternc_Tools_Mailbox_Export(array(
    "db" => $db
));

// Instanciate command line parser
$consoleParser = new Console_CommandLine(array(
    "version" => "0.1",
    "description" => "Exports Alternc mailboxes to a file for export."
));

// Configure command line parser
$consoleParser->add_version_option = false;
$consoleParser->addOption("output_file", array(
    "help_name" => "/tmp/out.json",
    "short_name" => "-o",
    "long_name" => "--output-file",
    "description" => "Export file name and path",
    'default'     => $service->default_output 
));
$consoleParser->addOption("exclude_mail", array(
    "help_name" => "/tmp/mailboxes.txt",
    "short_name" => "-e",
    "long_name" => "--exclude",
    "description" => "Path of a file containing mailboxes to exclude"
));

// Run the command line parser
try {
    $commandLineResult                  = $consoleParser->parse();
    // Run the service
    if( ! ($result = $service->run($commandLineResult ))){
        throw new \Exception("Export process failed");
    }
    $msg = $result["message"];
    $logger->logMessage(Logger\AbstractLogger::INFO,$msg);
    echo($msg."\n");
    
// Boom goes your request
} catch (\Exception $exc) {
    $msg = $exc->getMessage();
    $logger->logMessage(Logger\AbstractLogger::CRITICAL,$msg);
    $consoleParser->displayError($msg);
}


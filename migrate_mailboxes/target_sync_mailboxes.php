<?php
/**
 * 
 * Third part of the Mail export : rsync mailboxes, which you can use multiple times
 * 
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
    "description" => "Synchronizes mailboxes using an old => new mapper."
));

// Configure command line parser
$consoleParser->add_version_option = false;
$consoleParser->addOption("input_file", array(
    "help_name" => "/tmp/rsyncData.json",
    "short_name" => "-i",
    "long_name" => "--input-file",
    "description" => "Input file name and path",
    'default'     => $service->default_output
));
$consoleParser->addOption("rsync_log", array(
    "help_name" => "/tmp/rsyncLog.json",
    "short_name" => "-l",
    "long_name" => "--rsync-log",
    "description" => "Rsync log files",
    'default'     => $service->default_rsync_log
));
$consoleParser->addArgument("source", array(
    "help_name" => "source.server.fqdn",
    "description" => "Origin/Source server, IP or FQDN",
));
$consoleParser->addArgument("target", array(
    "help_name" => "target.server.fqdn",
    "description" => "Destination/Target server, IP or FQDN",
));

// Run the command line parser
try {
    $commandLineResult                  = $consoleParser->parse();
    // Run the service
    if( ! ($result = $service->rsync($commandLineResult ))){
        throw new \Exception("Import process failed");
    }
    $message = $result["message"];
    $logger->logMessage(Logger\AbstractLogger::INFO,$message);
    echo($message."\n");
    
// Boom goes your request
} catch (\Exception $exc) {
    $message = $exc->getMessage();
    $logger->logMessage(Logger\AbstractLogger::CRITICAL,$message);
    $consoleParser->displayError($message);
}


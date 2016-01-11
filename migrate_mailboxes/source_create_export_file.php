#!/usr/bin/php
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

$consoleParser->addOption("single_domain", array(
    "help_name" => "domain.com",
    "short_name" => "-d",
    "long_name" => "--single-domain",
    "description" => "A single domain to export"
));

$consoleParser->addOption("single_account", array(
    "help_name" => "foobar",
    "short_name" => "-a",
    "long_name" => "--single-account",
    "description" => "A single account name (i.e. AlternC login) to export\n"
));


$consoleParser->addOption("exclude_mails", array(
    "help_name" => "/tmp/mailboxes.txt",
    "long_name" => "--exclude-mails",
    "description" => "Path of a file containing mailboxes to exclude, separated by breaklines"
));

$consoleParser->addOption("include_mails", array(
    "help_name" => "/tmp/mailboxes.txt",
    "long_name" => "--include-mails",
    "description" => "Path of a file containing mailboxes to include, separated by breaklines"
));

$consoleParser->addOption("exclude_domain", array(
    "help_name" => "/tmp/domain.txt",
    "long_name" => "--exclude-domains",
    "description" => "Path of a file containing domains to include, separated by breaklines"
));

$consoleParser->addOption("include_domains", array(
    "help_name" => "/tmp/domain.txt",
    "long_name" => "--include-domains",
    "description" => "Path of a file containing domains to exclude, separated by breaklines"
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


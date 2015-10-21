<?php

/**
 * Retrieves from db and writes file
 * 
 * Caution, only AlternC > 3.0
 */
class Alternc_Tools_Domains_Import {

    /**
     * AlternC DB adapter
     * @var Db
     */
    protected $db;

    /**
     *
     * Class from alternc project
     * @var m_domain
     */
    protected $domain;

    /**
     *
     * @var int
     */
    public $default_quota = 1024;

    /**
     *
     * @var string
     */
    var $default_input = "/tmp/alternc.domains_export_out.json";

    /**
     * 
     * @param array $options
     * @throws \Exception
     */
    function __construct($options) {
        // Attempts to retrieve db
        if (isset($options["db"])) {
            $this->db = $options["db"];
        } else {
            throw new \Exception("Missing parameter db");
        }
    }

    /**
     * Checks if domain already in DB. 
     * 
     * @param array $domainData
     * @return boolean
     */
    function checkDomainExists($domainData) {

        $field = $domainData["domaine"];
        $success = false;

        // Build query
        $query = "SELECT domaine "
                . "FROM domaines d "
                . "WHERE d.domaine = '" . addslashes($field) . "'";

        // No record ? Exit
        if (count($this->query($query))) {
            $success = true;
        }
        return $success;
    }

    /**
     * Checks if login already in DB. 
     * 
     * @param array $domainData
     * @return boolean
     */
    function checkLoginExists($domainData) {

        $field = $domainData["login"];
        $success = false;

        // Build query
        $query = "SELECT login "
                . "FROM membres u "
                . "WHERE u.login = '" . addslashes($field) . "'";

        // No record ? Exit
        if (count($this->query($query))) {
            $success = true;
        }
        return $success;
    }


    /**
     * Creates the domain
     * 
     * Used the domain_add.php script by Antoine Beaupré for details. 
     * Snarky comments tend to be the most useful ones around here.
     * 
     * @global type $err
     * @param type $domainData
     * @param Console_CommandLine_Result $commandLineResult
     * @return int domain_id
     * @throws Exception
     */
    function createDomain($domainData, $commandLineResult) {

        global $err;

        // Retrieves command line options 
        $options = $commandLineResult->options;
        
        // Retrieve domain
        $domain = $domainData["domaine"];
        
        // Retrieve forced uid if exists
        
        

        return array("code" => 0, "message" => "ok", "domain_id" => $domain_id);
    }

    /**
     * 
     * @param array $options
     * @return boolean|array
     * @throws Exception
     */
    function getExportData($options) {

        // Read from input file
        if (!isset($options["input_file"])) {
            throw new Exception("Missing input file");
        }
        $filename = $options["input_file"];
        if (!$filename || !is_file($filename) || !is_readable($filename)) {
            throw new Exception("Failed to load file $filename");
        }
        $file_content = file_get_contents($filename);

        // Decode from JSON
        $exportList = json_decode($file_content, true);
        if (json_last_error()) {
            throw new Exception("JSON encoding failed: " . json_last_error_msg());
        }

        // Exit
        return $exportList;
    }

    /**
     * 
     * @param Console_CommandLine_Result $commandLineResult
     * @return boolean
     * @throws Exception
     */
    function import($commandLineResult) {

        $return = array("code" => 0, "message" => "OK");

        // Retrieves command line options 
        $options = $commandLineResult->options;
        $force_uid = (int) $options["force_uid"];

        // Retrieve export data
        $exportList = $this->getExportData($options);

        $errorsList = array();
        $successList = array();
        $rsyncExport = array();

        // Loop through domains
        foreach ($exportList as $domainData) {

            $domain = $domainData["domain"];
            try {

                // Login not exists : error
                if (!$this->checkLoginExists($domainData)  && ! is_null($force_uid) ) {
                    throw new Exception("Login does not exist: " . $domainData["login"] . " for $domain ");
                }

                // Domain not exists : error 
                if (!$this->checkDomainExists($domainData)) {
                    throw new Exception("Domain does not exist: " . $domainData["domain"] . " for $domain ");
                }

                // Create domain
                $creationResult = $this->createDomain($domainData, $options );

                // Failed to create? 
                if (!isset($creationResult["code"]) || $creationResult["code"] != 0) {
                    throw new Exception("Edomain creation error: " . $creationResult["message"] . " for $domain ");
                }

                // Attempts to retrieve domain_id
                if (isset($creationResult["domain_id"]) && $creationResult["domain_id"]) {
                    $domain_id = $creationResult["domain_id"];
                } else {
                    throw new Exception("Missing parameter domain_id");
                }

                // Add to export for rsync
                $rsyncExport[] = $this->buildRsyncExportSet($domain_id, $domainData);

                // Record success
                $successList[] = $domain;

                // Record errors
            } catch (\Exception $e) {
                $errorsList[] = $e->getMessage();
            }
        }

        $this->writeRsyncExport($rsyncExport, $commandLineResult);

        $return["successList"] = $successList;

        // Errors ? 
        if (count($errorsList)) {
            $return["code"] = 1;
            $return["message"] = "Errors occured";
            $return["errorsList"] = $errorsList;
        }

        // Exit
        return $return;
    }

    /**
     * Mysql query utility
     * 
     * @return array
     */
    function query($query = null) {

        // Query
        $connection = mysql_query($query);
        if (mysql_errno()) {
            throw new Exception("Mysql request failed. Errno #" . mysql_errno() . ": " . mysql_error());
        }

        // Build list
        $recordList = array();
        while ($result = mysql_fetch_assoc($connection)) {
            $recordList[] = $result;
        }

        // Exit
        return $recordList;
    }

    /**
     * 
     * @param Console_CommandLine_Result $commandLineResult
     * @return boolean
     * @throws Exception
     */
    function rsync($commandLineResult) {

        // Attempts to read export
        $exportList = $this->getRsyncExport($commandLineResult);

        // Retrieves options
        $options = $commandLineResult->options;
        
        // Attempts to retrieve log_file
        if (isset($options["log_file"]) && $options["log_file"]) {
            $log_file = $options["log_file"];
        } else {
            throw new \Exception("Missing parameter log_file");
        }
        
        // Retrieve arguments
        $argumentsList = $commandLineResult->args;

        // Attempts to retrieve source
        if (isset($argumentsList["source"]) && $argumentsList["source"]) {
            $source = $argumentsList["source"];
        } else {
            throw new \Exception("Missing parameter source");
        }

        // Attempts to retrieve target
        if (isset($options["target"]) && $argumentsList["target"]) {
            $target = $options["target"];
        } else {
            throw new \Exception("Missing parameter target");
        }

        // Loop through [new,old] sets
        foreach ($exportData as $set) {

            $errorList = array();

            try {

                // Attempts to retrieve domain
                if (isset($set["domain"]) && $set["domain"]) {
                    $domain = $set["domain"];
                } else {
                    throw new \Exception("Missing parameter domain for $domain");
                }
                // Attempts to retrieve new_path
                if (isset($set["new_path"]) && $set["new_path"]) {
                    $new_path = $set["new_path"];
                } else {
                    throw new Exception("Missing parameter new_path for $domain");
                }
                // Attempts to retrieve old_path
                if (isset($set["old_path"]) && $set["old_path"]) {
                    $old_path = $set["old_path"];
                } else {
                    throw new \Exception("Missing parameter old_path");
                }

                // Build the RSYNC command, note we don't keep owner and group
                $command = "rsync -rlpt " . escapeshellarg("$source:$old_path") . " " . escapeshellarg("$target:$new_path >> ".  escapeshellarg($log_file));
                
                // Run the Rsync command
                $code = 1;
                $output = array();
                exec( $command, $output, $code);
                if( $code != 0 ){
                    throw new Exception("Rsync failed for $domain : code#${code}, output : ".  json_encode($output));
                }
                
            } catch (Exception $exc) {
                $errorList[] = $exc->getTraceAsString();
            }
        }
    }

    /**
     * Writes a JSON file ready for use by the RSYNC function
     * 
     * @param array $exportList
     * @param Console_CommandLine_Result $commandLineResult
     * @return boolean
     * @throws Exception
     */
    function writeRsyncExport($exportList, $commandLineResult) {

        // Retrieves command line options 
        $options = $commandLineResult->options;

        // Encode to JSON
        $export_content = json_encode($exportList);
        if (json_last_error()) {
            throw new Exception("JSON encoding failed: " . json_last_error_msg());
        }

        // Write to output
        $output_file = $options["output_file"] ? $options["output_file"] : $this->default_output;
        if (!file_put_contents($output_file, $export_content)) {
            throw new Exception("Failed to write export $output_file");
        }

        return true;
    }

}

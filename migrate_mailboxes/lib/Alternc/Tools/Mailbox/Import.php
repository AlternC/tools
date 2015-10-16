<?php

/**
 * Retrieves from db and writes file
 * 
 * Caution, only AlternC > 3.0
 */
class Alternc_Tools_Mailbox_Import {

    /**
     * AlternC DB adapter
     * @var Db
     */
    protected $db;

    /**
     *
     * Class from alternc project
     * @var m_mail
     */
    protected $mail;

    /**
     *
     * @var int
     */
    public $default_quota = 1024;

    /**
     *
     * @var string
     */
    var $default_input = "/tmp/alternc.mailboxes_export_out.json";

    /**
     *
     * @var string
     */
    var $default_output = "/tmp/alternc.mailboxes_export_rsync.json";

    /**
     *
     * @var string
     */
    var $default_rsync_log = "/tmp/alternc.mailboxes_export_rsync.log";

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
     * 
     * Convenience for retrieving path data from db and array
     * 
     * @param int $mail_id
     * @param array $mailboxData
     * @return type
     * @throws \Exception
     */
    function buildRsyncExportSet($mail_id, $mailboxData) {

        $query = "SELECT "
                . "m.path "
                . "FROM address a "
                . "JOIN domaines d ON d.id = a.domain_id "
                . "JOIN mailbox m ON a.id = m.address_id "
                . "WHERE a.id = '" . addslashes($mail_id) . "'";

        $result = current($this->query($query));
        // Attempts to retrieve path
        if (isset($$result["path"]) && $result["path"]) {
            $new_path = $$result["path"];
        } else {
            throw new \Exception("Missing parameter path in new email " . $mailboxData["email"]);
        }

        // Attempts to retrieve path
        if (isset($mailboxData["path"]) && $mailboxData["path"]) {
            $old_path = $mailboxData["path"];
        } else {
            throw new \Exception("Missing parameter path in old email " . $mailboxData["email"]);
        }

        // Attempts to retrieve email
        if (isset($mailboxData["email"]) && $mailboxData["email"]) {
            $email = $mailboxData["email"];
        } else {
            throw new \Exception("Missing parameter email");
        }

        return array(
            "new_path" => "$new_path",
            "old_path" => "$old_path",
            "email" => "$email"
        );
    }

    /**
     * Checks if domain already in DB. 
     * 
     * @param array $mailboxData
     * @return boolean
     */
    function checkDomainExists($mailboxData) {

        $field = $mailboxData["domaine"];

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
     * @param array $mailboxData
     * @return boolean
     */
    function checkLoginExists($mailboxData) {

        $field = $mailboxData["login"];

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
     * Checks if mail already in DB. 
     * 
     * @param array $mailboxData
     * @return boolean
     */
    function checkMailExists($mailboxData) {

        $field = $mailboxData["email"];

        // Build query
        $query = "SELECT a.id "
                . "FROM address a "
                . "JOIN domaines d ON d.id = a.domain_id "
                . "WHERE CONCAT(a.address,'@',d.domaine) = '" . addslashes($field) . "'";

        // No record ? Exit
        if (count($this->query($query))) {
            $success = true;
        }
        return $success;
    }

    /**
     * Creates the mailbox
     * 
     * Used the mail_add.php script by Antoine Beaupré for details. 
     * Snarky comments tend to be the most useful ones around here.
     * 
     * @global type $err
     * @param type $mailboxData
     * @return int mail_id
     * @throws Exception
     */
    function createMail($mailboxData) {

        global $err;

        $email = $mailboxData["email"];

        // Will create a real mailbox if path exists
        $path = $mailboxData["path"];

        // Will add recipients if recipients provided
        $recipients = $mailboxData["recipients"];

        // Create the mail
        $mail_id = $this->mail->create($mailboxData["dom_id"], $mailboxData["address"]);
        if (!$mail_id) {
            throw new Exception("Failed to create mailbox for $email : " . $err->errstr());
        }

        // Set password
        if (!$this->mail->set_passwd($mail_id, $mailboxData["password"])) {
            throw new Exception("Failed to set password for $email : " . $err->errstr());
        }

        // Set details 
        if (!$this->mail->set_details($mail_id, ($path ? true : false), $this->default_quota, $recipients)) {
            throw new Exception("failed to set details for $email : " . $err->errstr());
        }

        return array("code" => 0, "message" => "ok", "mail_id" => $mail_id);
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
        $exportList = json_decode($file_content);
        if (json_last_error()) {
            throw new Exception("JSON encoding failed: " . json_last_error_msg());
        }

        // Exit
        return $exportList;
    }

    /**
     * Reads from JSON file with Rsync Data
     * 
     * Really, a copy of this->getExportData, but provided for code legitibility
     * 
     * @param Console_CommandLine_Result $commandLineResult
     * @return array
     * @throws Exception
     */
    function getRsyncExport($commandLineResult) {

        // Retrieves command line options 
        $options = $commandLineResult->options;

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
        $exportList = json_decode($file_content);
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

        // Retrieve export data
        $exportList = $this->getExportData($options);

        $errorsList = array();
        $successList = array();
        $rsyncExport = array();

        // Loop through mailboxes
        foreach ($exportList as $mailboxData) {

            $email = $mailboxData["email"];
            try {

                // Login not exists : error
                if (!$this->checkLoginExists($mailboxData)) {
                    throw new Exception("Login does not exist: " . $mailboxData["login"] . " for $email ");
                }

                // Domain not exists : error 
                if (!$this->checkDomainExists($mailboxData)) {
                    throw new Exception("Domain does not exist: " . $mailboxData["domain"] . " for $email ");
                }

                // Mail not exists : error
                if ($this->checkMailExists($mailboxData)) {
                    throw new Exception("Address $email already exists.");
                }

                // Create mail
                $creationResult = $this->createMail($mailboxData);

                // Failed to create? 
                if (!isset($creationResult["code"]) || $creationResult["code"] != 0) {
                    throw new Exception("Email creation error: " . $creationResult["message"] . " for $email ");
                }

                // Attempts to retrieve mail_id
                if (isset($creationResult["mail_id"]) && $creationResult["mail_id"]) {
                    $mail_id = $creationResult["mail_id"];
                } else {
                    throw new Exception("Missing parameter mail_id");
                }

                // Add to export for rsync
                $rsyncExport[] = $this->buildRsyncExportSet($mail_id, $mailboxData);

                // Record success
                $successList[] = $email;

                // Record errors
            } catch (\Exception $e) {
                $errorsList[] = $e->getMessage();
            }
        }

        $this->writeRsyncExport($rsyncExport, $commandLineResult->options);

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

                // Attempts to retrieve email
                if (isset($set["email"]) && $set["email"]) {
                    $email = $set["email"];
                } else {
                    throw new \Exception("Missing parameter email for $email");
                }
                // Attempts to retrieve new_path
                if (isset($set["new_path"]) && $set["new_path"]) {
                    $new_path = $set["new_path"];
                } else {
                    throw new Exception("Missing parameter new_path for $email");
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
                    throw new Exception("Rsync failed for $email : code#${code}, output : ".  json_encode($output));
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

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
     * @var string
     */
    var $default_input = "/tmp/alternc.mailboxes_export_out.json";

    /**
     * 
     * @param array $options
     * @throws \Exception
     */
    function __construct( $options ) {
        // Attempts to retrieve db
        if (isset($options["db"])) {
            $this->db = $options["db"];
        } else {
            throw new \Exception("Missing parameter db");
        }
    }

    /**
     * 
     * 
     * @return array
     */
    function getAdressList ( $options = null ){
        
        $exclude_query = "";
        $excludeMailList = $this->getExcludeMailList( $options );
        if( count( $excludeMailList )){
            $exclude_query = "AND CONCAT(a.address,'@',d.domaine) NOT IN ('".implode("','", $excludeMailList)."') ";
        }
        
        // Build query
        $query = "SELECT CONCAT(a.address,'@',d.domaine) AS email,a.address, d.domaine, a.password, m.path, u.login "
                . "FROM address a, mailbox m, domaines d, membres u "
                . "WHERE a.id = m.address_id "
                . "AND d.id = a.domain_id "
                . "AND u.uid = d . compte "
                . "AND a.type != 'mailman'"
                . $exclude_query
                . ";";
        
        // Query
        $connection = mysql_query($query);
        if(mysql_errno()){
            throw new Exception("Mysql request failed. Errno #".  mysql_errno(). ": ".  mysql_error());
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
     * @param array $options
     * @return boolean|array
     * @throws Exception
     */
    function getExportData( $options ){

        // Read from input file
        if( ! isset($options["input_file"])){
            throw new Exception("Missing input file");
        }
        $filename = $options["input_file"];
        if( ! $filename || ! is_file( $filename) || !is_readable($filename)){
            throw new Exception("Failed to load file $filename");
        }
        $file_content = file_get_contents($filename);
        
        // Decode from JSON
        $exportList = json_decode($file_content);
        if(json_last_error()){
            throw new Exception("JSON encoding failed: ".json_last_error_msg());
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
    function run($commandLineResult){
        
        $return = array("code" => 0, "message" => "OK");
        
        // Retrieves command line options 
        $options = $commandLineResult->options;
        
        // Retrieve export data
        $exportList = $this->getExportData($options);
        
        $errorsList = array();
        $successList = array();
        
        // Loop through mailboxes
        foreach( $exportList as $mailboxData ){
            
            $email = $mailboxData["email"];
            try{
                
                // Login not exists : error
                if (!$this->checkLoginExists($mailboxData)) {
                    throw new Exception("Login does not exist: " . $mailboxData["login"]. " for $email ");
                }

                // Domain not exists : error 
                if (!$this->checkDomainExists($mailboxData)) {
                    throw new Exception("Domain does not exist: " . $mailboxData["domain"] . " for $email ");
                }

                // Mail not exists : create 
                if (!$this->checkMailExists($mailboxData)) {
                    $creationResult = $this->createMail($mailboxData);
                    
                    // Failed to create? 
                    if( !isset($creationResult["code"]) || $creationResult["code"] != 0 ){
                        throw new Exception("Email creation error: " . $creationResult["message"] . " for $email ");
                    }
                    
                    // Record success
                    $successList[] = $email;
                }
                
            // Record errors
            }  catch (\Exception $e){
                $errorsList[] = $e->getMessage();
            }
        }
        
        $return["successList"] = $successList;
        
        // Errors ? 
        if( count( $errorsList )){
            $return["code"] = 1;
            $return["message"] = "Errors occured";
            $return["errorsList"] = $errorsList;
        }
        
        // Exit
        return $return;
    }

    
    function checkLoginExists( $mailboxData ){
        
    }
    
    
    function checkDomainExists( $mailboxData ){
        
    }
    
    
    function checkMailExists( $mailboxData ){
        
    }
    
    function createMail( $mailboxData ){
        
    }
    
    
    
    
}
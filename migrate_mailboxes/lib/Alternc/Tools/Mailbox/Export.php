<?php

/**
 * Retrieves from db and writes file
 * 
 * Caution, only AlternC > 3.0
 */
class Alternc_Tools_Mailbox_Export {
    
    
    /**
     * AlternC DB adapter
     * @var Db
     */
    protected $db;

    /**
     *
     * @var string
     */
    var $default_output = "/tmp/alternc.mailboxes_export_out.json";

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
        $query = "SELECT "
                . " CONCAT(a.address,'@',d.domaine) AS email,"
                . " a.id, "
                . " a.address, "
                . " d.domaine, "
                . " d.id as dom_id, "
                . " a.password, "
                . " m.path, "
                . " r.recipients, "
                . " u.login "
                . "FROM address a "
                . "JOIN domaines d ON d.id = a.domain_id "
                . "JOIN membres u ON u.uid = d.compte "
                . "LEFT JOIN recipient r ON a.id = r.address_id "
                . "LEFT JOIN mailbox m ON a.id = m.address_id "
                . "WHERE 1 "
                . "AND a.type != 'mailman' "
                . $exclude_query
                . ";";
        
        // Query
        $connection = mysql_query($query);
        if(mysql_errno()){
            throw new Exception("Mysql request failed. Errno #".  mysql_errno(). ": ".  mysql_error());
        }
        
        // Build list
        $recordList = array();
        while ($record = mysql_fetch_assoc($connection)) {
            
            $recordList[] = $record;
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
    function getExcludeMailList( $options ){

        if( ! isset($options["exclude_mail"])){
            return false;
        }
        $filename = $options["exclude_mail"];
        if( ! $filename || ! is_file( $filename) || !is_readable($filename)){
            throw new Exception("Failed to load file $filename");
        }
        $fileContent = file($filename);
        
        foreach ($fileContent as $line) {
            preg_match_all("/\S*@\S*/", $line, $matches);
            if( count($matches)){
                foreach( $matches as $emailMatches){
                    $result[] = $emailMatches[0];
                }
            }
            
        }
        return $result;
        
    }
    
    /**
     * 
     * @param Console_CommandLine_Result $commandLineResult
     * @return boolean
     * @throws Exception
     */
    function run($commandLineResult){
        
        // Retrieves command line options 
        $options = $commandLineResult->options;
        
        // Retrieve addresses list
        $exportList = $this->getAdressList($options);
        
        // Encode to JSON
        $export_content = json_encode($exportList);
        if(json_last_error()){
            throw new Exception("JSON encoding failed: ".json_last_error_msg());
        }
        
        // Write to output
        $output_file = $options["output_file"] ? $options["output_file"] : $this->default_output;
        if( !file_put_contents($output_file, $export_content)){
            throw new Exception("Failed to write export $output_file");
        }
        
        // Exit
        return array("code" => 0, "message" => "Wrote export content to $output_file");
    }

}
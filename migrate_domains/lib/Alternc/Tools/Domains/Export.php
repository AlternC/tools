<?php

/**
 * Retrieves from db and writes file
 * 
 * Caution, only AlternC > 3.0
 */
class Alternc_Tools_Domains_Export {
    
    
    /**
     * AlternC DB adapter
     * @var Db
     */
    protected $db;

    /**
     *
     * @var string
     */
    var $default_output = "/tmp/alternc.domains_export_out.json";

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
    function getDomainList ( $options = null ){
        
        $exclude_query = "";
        $excludeDomainList = $this->getExcludeDomainList( $options );
        if( count( $excludeDomainList )){
            $exclude_query = "AND d.domaine NOT IN ('".implode("','", $excludeDomainList)."') ";
        }
        
        // Build query
        $query = "SELECT c.login, d.* "
                . "FROM domaines d "
                . "JOIN membres c ON d.compte = c.uid "
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
            $recordList[$record["domaine"]] = $record;
        }
        
        // Fetch subdomains for domain
        foreach( $recordList as $domain => $domainData ){
            $domainData["sub_domains"] = $this->getSubdomains( $domain );
            $recordList[$domain] = $domainData;
        }
        
        // Exit
        return $recordList;
    }
    
    /**
     * 
     * @param string $domain
     * @return array
     * @throws Exception
     */
    public function getSubdomains( $domain ){
        
        $query = "SELECT s.* "
                . "FROM domaines d "
                . "JOIN sub_domaines s ON s.domaine = d.id "
                . "WHERE s.domaine = '".$domain."'";
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
        echo($domain."\n");
        return $recordList;
        
    }
    
    /**
     * 
     * @param array $options
     * @return boolean|array
     * @throws Exception
     */
    function getExcludeDomainList( $options ){

        if( ! isset($options["exclude_domain"]) ){
            return array();
        }
        $filename = $options["exclude_domain"];
        if( ! $filename || ! is_file( $filename) || !is_readable($filename)){
            throw new Exception("Failed to load file $filename");
        }
        $fileContent = file($filename);
        
        foreach ($fileContent as $line) {
            preg_match_all("/\S+/", $line, $matches);
            if( count($matches)){
                foreach( $matches as $domainMatches){
                    $result[] = $domainMatches[0];
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
        $exportList = $this->getDomainList($options);
        
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

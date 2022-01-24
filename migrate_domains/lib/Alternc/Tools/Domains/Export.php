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

        $include_query = "";
        $includeDomainList = $this->getIncludeDomainList( $options );
        if( count( $includeDomainList )){
            $include_query = "AND d.domaine IN ('".implode("','", $includeDomainList)."') ";
        }

        $include_accounts = "";
        $includeAccountsIdList = $this->getIncludeAccountsIdList( $options );
        if( count( $includeAccountsIdList )){
            $include_accounts = "AND d.compte IN ('".implode("','", $includeAccountsIdList)."') ";
        }
        
        // Build query
        $query = "SELECT c.login, d.* "
                . "FROM domaines d "
                . "JOIN membres c ON d.compte = c.uid "
                . $exclude_query
                . $include_query
                . $include_accounts
                . ";";
        
        // Query
        $this->db->query($query);

        // Build list
        $recordList = array();
        while ($this->db->next_record()) {
            $recordList[$this->db->Record["domaine"]] = $this->array_to_hash($this->db->Record);
        }
        
        // Fetch subdomains for domain
        foreach( $recordList as $domain => $domainData ){
            $recordList[$domain]["sub_domains"] = $this->getSubdomains( $domain );
            $manualzone=$this->getManualZoneInfo($domain);
            if ($manualzone) {
                $recordList[$domain]["manual_zone"] = $manualzone;
            }
        }
        
        // Exit
        return $recordList;
    }


    public function getManualZoneInfo( $domain ) {
        $zonefile = "/var/lib/alternc/bind/zones/".$domain;
        if (!file_exists($zonefile)) {
            return false;
        }
        $f=fopen($zonefile,"rb");
        $manualzone="";
        $inmanual=false;
        while ($s=fgets($f,1024)) {
            if ($inmanual) $manualzone.=$s;
            if (preg_match("#;;; END ALTERNC AUTOGENERATE CONFIGURATION#",$s)) {
                $inmanual=true;
            }
        }
        return trim($manualzone);
    }
    
    
    /**
     * 
     * @param string $domain
     * @return array
     * @throws Exception
     */
    public function getSubdomains( $domain ){
        
        $query = "SELECT * "
               . "FROM sub_domaines "
               . "WHERE domaine = '".addslashes($domain)."'";
        // Query
        $this->db->query($query);
        
        // Build list
        $recordList = array();
        while ($this->db->next_record()) {
            $recordList[] = $this->array_to_hash($this->db->Record);
        }
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
     * @param array $options
     * @return boolean|array
     * @throws Exception
     */
    function getIncludeDomainList( $options ){

        if( ! isset($options["include_domain"]) ){
            return array();
        }
        $filename = $options["include_domain"];
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
     * @param array $options
     * @return boolean|array
     * @throws Exception
     */
    function getIncludeAccountsIdList( $options ){

        if( ! isset($options["include_accounts"]) ){
            return array();
        }
        $filename = $options["include_accounts"];
        if( ! $filename || ! is_file( $filename) || !is_readable($filename)){
            throw new Exception("Failed to load file $filename");
        }
        $fileContent = file($filename);
        
        foreach ($fileContent as $line) {
            preg_match_all("/\S+/", $line, $matches);
            if( count($matches)){
                foreach( $matches as $domainMatches){
                    $this->db->query("SELECT uid FROM membres WHERE login='".addslashes($domainMatches[0])."';");
                    if ($this->db->next_record()) {
                        $result[] = $this->db->Record["uid"];
                    }
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


    function array_to_hash($array) {
        $res=[];
        foreach($array as $k=>$v) {
            if ($k=="0" || intval($k)!=0) continue;
            $res[$k]=$v;
        }
        return $res;
    }

}

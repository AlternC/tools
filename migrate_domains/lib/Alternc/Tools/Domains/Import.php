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
     * @var m_dom
     */
    protected $domain;

    /**
     *
     * @var array 
     */
    public $domainesTypeCache = array();

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
	global $dom;
        // Attempts to retrieve db
        if (isset($options["db"])) {
            $this->db = $options["db"];
        } else {
            throw new \Exception("Missing parameter db");
        }
	$this->domain = $dom; 
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

	global $cuid;
	global $get_quota_cache;
        $field = $domainData["login"];
        $success = false;
	
        // Build query
        $query = "SELECT uid "
                . "FROM membres u "
                . "WHERE u.login = '" . addslashes($field) . "'";


        // No record ? Exit
        $result = $this->query($query);
	if (count( $result )) {
	    $cuid = $result[0]["uid"];
	    $this->giveQuota( array("cuid" => $cuid, "type" => "dom") );
            $success = true;
        }
	return $success;
    }

    function giveQuota( $options ){

	    global $cuid;
	    global $get_quota_cache;
	    $cuid = $options["cuid"];
	    $type = $options["type"];
	    $get_quota_cache[$cuid][$type] = array( "u" => 0, "t" => 1);

    }


    /**
     * Creates the domain
     * 
     * @global type $err
     * @param type $domainData
     * @param Console_CommandLine_Result $commandLineResult
     * @return int domain_id
     * @throws Exception
     */
    function createDomain($domainData, $commandLineResult) {

        global $err;
	global $cuid;

        // Retrieves command line options 
        $options = $commandLineResult->options;
        
	// Retrieve forced uid if exists
        $force_uid = (int) $options["force_uid"];
  
	if( $force_uid ) {
	    $this->giveQuota( array("cuid" => $force_uid, "type" => "dom" ) );
	} 
	 
        // Retrieve domain
        $domain = $domainData["domaine"];
        $dns = $domainData["gesdns"];
        $dns_action = $domainData["dns_action"];
        $dns_result = $domainData["dns_result"];
        $mail = $domainData["gesmx"];
        $zonettl = $domainData["zonettl"];
        $noerase = $domainData['noerase'];
 	$force = true;
	$isslave = false;
	$slavedom = "";
	// Attempts to insert domain
	$this->domain->lock();
       	if( ! $this->domain->add_domain( $domain, $dns, $noerase, $force, $isslave, $slavedom) ){
	    throw new Exception("Failed to create $domain : ".$err->errstr() );	
	}

	// Retrieve new domain id
	$query = "SELECT id FROM domaines WHERE domaine='$domain'";
	$result = current( $this->query( $query ) );
	$domain_id = (int) $result['id'];
	if( ! $domain_id ){
	    throw new Exception("Failed to retrieve domain_id for $domain " );	
	}

	// Attempts to insert subdomains

	$subdomainsList = $domainData["sub_domains"];
	$subdomainsErrorList = array();
	foreach( $subdomainsList as $subdomainData ){

		$compte = $subdomainData["compte"];
		$domain = $subdomainData["domaine"];
		$sub = $subdomainData["sub"];
		$dest = $subdomainData["valeur"];
		$type = $subdomainData["type"];
		$web_action = $subdomainData["web_action"];
		$web_result = $subdomainData["web_result"];
		$enable = $subdomainData["enable"];
		if( ! array_key_exists( $type, $this->domainesTypeCache ) ){
			$valid = $this->domain->domains_type_target_values( $type );
			$this->domainesTypeCache[$type] = $valid;
		}
		if( ! $this->domainesTypeCache[$type] ){
			$subdomainsErrorList[] = "Type '$type' is invalid ($sub.$domain)";
			continue;
		}

		if( ! $this->domain->set_sub_domain($domain, $sub, $type, $dest ) ) {
			$subdomainsErrorList[] = "Failed to create $sub.$domain : ". $err->errstr();	
		}
	}
	if( count( $subdomainsErrorList ) ){
		return array("code" => 1, "message" => "Errors with subdomains : ".implode( " / ",$subdomainsErrorList) );
	}

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
            throw new Exception("JSON encoding failed: " . json_last_error());
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

            $domain = $domainData["domaine"];
            try {

                // Login not exists : error
                if (!$this->checkLoginExists($domainData) &&  ! $force_uid ) {
                    throw new Exception("Login does not exist: " . $domainData["login"] . " for $domain ");
                }

                // Domain not exists : error 
                if ($this->checkDomainExists($domainData)) {
                    throw new Exception("Domain exists: " . $domain );
                }

                // Create domain
                $creationResult = $this->createDomain($domainData, $commandLineResult);

                // Failed to create? 
                if (!isset($creationResult["code"]) || $creationResult["code"] != 0) {
                    throw new Exception("Domain $domain creation error: " . $creationResult["message"] );
                }

                // Attempts to retrieve domain_id
                if (isset($creationResult["domain_id"]) && $creationResult["domain_id"]) {
                    $domain_id = $creationResult["domain_id"];
                } else {
                    throw new Exception("Missing parameter domain_id");
                }

                // Record success
                $successList[] = $domain;

                // Record errors
            } catch (\Exception $e) {
                $errorsList[] = $e->getMessage();
            }
        }

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



}

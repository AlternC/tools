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
     * 
     * @return array
     */
    function getFinalAdressList($options = null) {

        $includeMailList = $this->getIncludeMailList($options);
        if (count($includeMailList)) {
            $includeMailList = array_unique($includeMailList);
        }
        $excludeMailList = $this->getExcludeMailList($options);
        if (count($excludeMailList)) {
            $excludeMailList = array_unique($excludeMailList);
        }

        // Addresses that are included AND excluded will be excluded (filter out by default)
        foreach ($excludeMailList as $email) {
            if (in_array($email, $includeMailList)) {
                unset($includeMailList[array_search($email, $includeMailList)]);
            }
        }

        if (!count($includeMailList)) {
            throw new \Exception("Your filters returned an empty list.");
        }

        return $this->fetchFinalListFromDb($includeMailList);
    }

    /**
     * 
     * @param array $options
     * @return boolean|array
     * @throws Exception
     */
    function getIncludeMailList($options) {

        $intersect = array_intersect(array(
            "single_domain",
            "single_account",
            "include_mails",
            "include_domains"
                ), array_keys($options));
        if (!count($intersect)) {
            // Error, will return nothing
            throw new \Exception("You need to provide at least one selector.");
        }

        $resultArray = array();
        foreach ($options as $filter => $value) {
            switch ($filter) {
                case "single_domain":
                    $tmpArray = $this->fetchAddressListFromDb("d.domaine = '" . addslashes($value) . "'");
                    $resultArray = array_merge($resultArray, $tmpArray);
                    break;
                case "single_account":
                    $tmpArray = $this->fetchAddressListFromDb("u.login = '" . addslashes($value) . "'");
                    $resultArray = array_merge($resultArray, $tmpArray);
                    break;
                case "include_mails":
                    $tmpArray = $this->getListFromFile($value);
                    $resultArray = array_merge($resultArray, $tmpArray);
                    break;
                case "include_domains":
                    $tmpArray = $this->getListFromFile($value);
                    $tmpArray = $this->fetchAddressListFromDb("d.domaine IN ('" . implode("','", $tmpArray) . "')");
                    $resultArray = array_merge($resultArray, $tmpArray);
                    break;
            }
        }
        return $resultArray;
    }

    /**
     * 
     * @param array $options
     * @return boolean|array
     * @throws Exception
     */
    function getExcludeMailList($options) {

        $resultArray = array();
        foreach ($options as $filter => $value) {

            switch ($filter) {
                case "exclude_mails":
                    $tmpArray = $this->getListFromFile($value);
                    $resultArray = array_merge($resultArray, $tmpArray);
                    break;
                case "exclude_domains":
                    $tmpArray = $this->getListFromFile($value);
                    $tmpArray = $this->fetchAddressListFromDb("d.domaine IN ('" . implode("','", $tmpArray) . "')");
                    $resultArray = array_merge($resultArray, $tmpArray);
                    break;
            }
        }
        return $resultArray;
    }

    /**
     * 
     * @param type $filename
     * @return array
     * @throws Exception
     */
    function getListFromFile($filename) {


        if (!$filename || !is_file($filename) || !is_readable($filename)) {
            throw new Exception("Failed to load file $filename");
        }
        $fileContent = file($filename);

        foreach ($fileContent as $line) {
            $result[] = trim($line);
        }
        return $result;
    }

    /**
     * Utility function for DB retrieval of addresses
     * 
     * @param string $where
     * @return array
     * @throws Exception
     */
    function fetchAddressListFromDb($where) {

        // Build query
        $query = "SELECT "
                . " CONCAT(a.address,'@',d.domaine) AS email "
                . "FROM address a "
                . "JOIN domaines d ON d.id = a.domain_id "
                . "JOIN membres u ON u.uid = d.compte "
                . "LEFT JOIN recipient r ON a.id = r.address_id "
                . "LEFT JOIN mailbox m ON a.id = m.address_id "
                . "WHERE a.type != 'mailman' "
                . "AND $where"
                . ";";

        // Query
        global $db;
        $db->query($query);
        if (mysql_errno()) {
            throw new Exception("Mysql request failed. Errno #" . mysql_errno() . ": " . mysql_error());
        }

        // Build list
        $recordList = array();
        while ($db->next_record()) {
            $record = $db->Record;
            $recordList[] = $record["email"];
        }

        // Exit
        return $recordList;
    }

    /**
     * Retrieves complete informations from the database based on an array of emails
     * 
     * @param array $addressList
     * @return array
     * @throws Exception
     */
    function fetchFinalListFromDb($addressList) {

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
                . "WHERE a.type != 'mailman' "
                . "AND CONCAT(a.address,'@',d.domaine) IN ('" . implode("','", $addressList) . "')"
                . ";";

        // Query
        global $db;
        $db->query($query);
        if (mysql_errno()) {
            throw new Exception("Mysql request failed. Errno #" . mysql_errno() . ": " . mysql_error());
        }

        // Build list
        $recordList = array();
        while ($db->next_record()) {
            $record = $db->Record;
            $recordList[$record["email"]] = $record;
        }

        // Exit
        return $recordList;
    }

    function fixDb($commandLineResult) {

        // Retrieve addresses list
        $exportList = $this->getAdressList($options);

        // Build query
        $query = '
	    SELECT a1.id as parent, a2.id as child
	    FROM address a1  
	    JOIN address a2 
	    ON a1.domain_id = a2.domain_id 
	    AND a1.id != a2.id 
	    AND a2.address LIKE (concat(a1.address,"-%"))
	    AND a1.type != "mailman" 
	    AND a2.type != "mailman"
	    AND a1.`password` = ""
	    AND a2.`password` = ""
	    ';

        // Query
        $connection = mysql_query($query);
        if (mysql_errno()) {
            throw new Exception("Mysql request failed. Errno #" . mysql_errno() . ": " . mysql_error());
        }

        // Build list
        $updateIdList = array();
        while ($record = mysql_fetch_assoc($connection)) {
            $parent = $record["parent"];
            $child = $record["child"];
            if (!in_array($parent, $updateIdList)) {
                $updateIdList[] = $parent;
            }
            if (!in_array($child, $updateIdList)) {
                $updateIdList[] = $child;
            }
        }
        if (!count($updateIdList)) {
            return array("code" => 0, "message" => "Nothing to do");
        }
        $query_update = "UPDATE address 
	    SET type = 'mailman'
	    WHERE id in (" . implode(",", $updateIdList) . ")";

        $connection = mysql_query($query_update);
        if (mysql_errno()) {
            throw new Exception("Mysql request failed. Errno #" . mysql_errno() . ": " . mysql_error());
        }
        // Exit
        return array("code" => 0, "message" => "Changed type for address list: " . implode(",", $updateIdList));
    }

    function checkOptionsConsistency(&$options) {

        // Clean
        foreach ($options as $key => $value) {
            if (null == $value) {
                unset($options[$key]);
            }
        }

//        $params = $options;
//
//        // Exclude output
//        if (isset($params["output_file"])) {
//            unset($paryou cannoams["output_file"]);
//        }
//
//        // Do not allow more than one parameter if a "single"
//        if (isset($params["single_domain"]) || isset($params["single_account"])) {
//            if (count($params) > 1) {
//                throw new \Exception("You cannot use more than one option if a single option is selected.");
//            }
//        }
    }

    /**
     * 
     * @param Console_CommandLine_Result $commandLineResult
     * @return boolean
     * @throws Exception
     */
    function run($commandLineResult) {

        // Retrieves command line options 
        $options = $commandLineResult->options;

        // Checks command line consistency
        $this->checkOptionsConsistency($options);

        // Retrieve addresses list
        $exportList = $this->getFinalAdressList($options);

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

        // Exit
        return array("code" => 0, "message" => "Wrote export content to $output_file");
    }


}

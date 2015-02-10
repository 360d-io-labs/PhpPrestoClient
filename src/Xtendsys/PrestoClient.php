<?php
/*
PrestoClient provides a way to communicate with Presto server REST interface. Presto is a fast query
engine developed by Facebook that runs distributed queries against Hadoop HDFS servers.

Copyright 2013 Xtendsys | xtendsys.net

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at:

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.*/

namespace Xtendsys;

require_once __DIR__.'/PrestoException.php';

class PrestoClient {
	/**
	 * The following parameters may be modified depending on your configuration
	 */
	private $source = 'PhpPrestoClient';
	private $version = '0.2';
	private $maximumRetries = 5;
	private $prestoUser = "presto";
	private $prestoSchema = "default";
	private $prestoCatalog = "hive";
	private $userAgent = "";
	
	//Do not modify below this line
	private $nextUri =" ";
	private $infoUri = "";
	private $partialCancelUri = "";
	private $state = "NONE";
	
	private $url;
	private $headers;
	private $result;
	private $request;
	

	public $HTTP_error;
	public $data = array();


	/**
	 * Constructs the presto connection instance
	 *
	 * @param $connectUrl
	 * @param $catalog
	 */
	public function __construct($connectUrl,$catalog){
		$this->url = $connectUrl;
		$this->prestoCatalog = $catalog;
	}
	/**
	 * Return Data as an array. Check that the current status is FINISHED
	 *
	 * @return array|false
	 */
	public function GetData(){
		if ($this->state!="FINISHED"){
			return false;
		}
		return $this->data;
	}

	/**
	 * prepares the query
	 *
	 * @param $query
	 * @return bool
	 * @throws Exception
	 */
	public function Query($query) {
		
		$this->data=array();
		$this->userAgent = $this->source."/".$this->version;
		
		$this->request = $query;
		//check that no other queries are already running for this object
		if ($this->state === "RUNNING") {
			return false;
		}

		/**
		 * check that query is completed, and that we don't start
		 * a new query before the previous is finished
		 */
		if ($query="") {
			return false;
		}
		
		$this->headers = array(
			"X-Presto-User: ".$this->prestoUser,
			"X-Presto-Catalog: ".$this->prestoCatalog,
			"X-Presto-Schema: ".$this->prestoSchema,
			"User-Agent: ".$this->userAgent);
		
		$connect = \curl_init();
		\curl_setopt($connect,CURLOPT_URL, $this->url);
		\curl_setopt($connect,CURLOPT_HTTPHEADER, $this->headers);
		\curl_setopt($connect,CURLOPT_RETURNTRANSFER, 1);
		\curl_setopt($connect,CURLOPT_POST, 1);
		\curl_setopt($connect,CURLOPT_POSTFIELDS, $this->request);
		
		$this->result = \curl_exec($connect);
		
		$httpCode = \curl_getinfo($connect, CURLINFO_HTTP_CODE);
	
		if($httpCode!="200"){
			
			$this->HTTP_error = $httpCode;
			throw new PrestoException("HTTP ERRROR: $this->HTTP_error");
		}
		
		//set status to RUNNING
		curl_close($connect);
		$this->state = "RUNNING";
		return true;	
	}


	/**
	 * waits until query was executed
	 *
	 * @return bool
	 * @throws PrestoException
	 */
	function WaitQueryExec() {
		
		$this->GetVarFromResult();
		
		while ($this->nextUri){
			
			usleep(500000);
			$this->result = file_get_contents($this->nextUri);
			$this->GetVarFromResult();
		}
		
		if ($this->state!="FINISHED"){
			throw new PrestoException("Incoherent State at end of query");}
		
		return true;
		
	}
	
	/** 
	 * Provide Information on the query execution
	 * The server keeps the information for 15minutes
	 * Return the raw JSON message for now
	 *
	 * @return string
	 */
	function GetInfo() {
		
		$connect = \curl_init();
        \curl_setopt($connect,CURLOPT_URL, $this->infoUri);
        \curl_setopt($connect,CURLOPT_HTTPHEADER, $this->headers);
		$infoRequest = \curl_exec($connect);
		\curl_close($connect);
		
		return $infoRequest;
	}
	
	private function GetVarFromResult() {
		/* Retrieve the variables from the JSON answer */
		
	  	$decodedJson = json_decode($this->result); 
	  
	  	if (isset($decodedJson->{'nextUri'})){
	  	$this->nextUri = $decodedJson->{'nextUri'};} else {$this->nextUri = false;}
	  
	  	if (isset($decodedJson->{'data'})){
	  	$this->data = array_merge($this->data,$decodedJson->{'data'});} 
	  
	  	if (isset($decodedJson->{'infoUri'})){
	  	$this->infoUri = $decodedJson->{'infoUri'};}
	  
	  	if (isset($decodedJson->{'partialCancelUri'})){
	  	$this->partialCancelUri = $decodedJson->{'partialCancelUri'};}
	  
	  	if (isset($decodedJson->{'stats'})){
	  		$status = $decodedJson->{'stats'};
	  		$this->state = $status->{'state'};}
		}
	
	/**
	 * Provide a function to cancel current request if not yet finished
	 */
	private function Cancel(){
		if (!isset($this->partialCancelUri)){
			return false; 
			
		$connect = \curl_init();
		\curl_setopt($connect,CURLOPT_URL, $this->partialCancelUri);
		\curl_setopt($connect,CURLOPT_HTTPHEADER, $this->headers);
		$infoRequest = \curl_exec($connect);
		\curl_close($connect);
		
		$httpCode = \curl_getinfo($connect, CURLINFO_HTTP_CODE);
	
		if($httpCode!="204"){
			return false;}else{
		return true;}
		}	
	}
	
}

?>

<?php
require_once('PhpPrestoClient.php');

//Create a new connection object. Provide URL and catalog as parameters
$presto = new PhpPrestoClient("http://10.2.7.1:8080/v1/statement","hive");

//Prepare your sql request
try {
	$presto -> PrestoQuery("select * from hive.default.mytable");
	} catch (Exception $e) {
		return false;}

//Execute the request and build the result
$presto->WaitQueryExec();

//Get the result
$answer=$presto->GetData();

?>

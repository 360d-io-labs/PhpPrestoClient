<?php
/*
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

use \Xtendsys\PrestoClient;
use \Xtendsys\PrestoException;

require_once(__DIR__.'/../src/Xtendsys/PrestoClient.php');

//Create a new connection object. Provide URL and catalog as parameters
$presto = new PrestoClient("http://127.0.0.1:8080/v1/statement","hive");

//Prepare your sql request
try {
	$presto->Query("select * from hive.default.mytable");
} catch (PrestoException $e) {
	var_dump($e);
}

//Execute the request and build the result
$presto->WaitQueryExec();

//Get the result
$answer = $presto->GetData();

var_dump($answer);

?>

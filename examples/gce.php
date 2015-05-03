<?php
/*
 * Copyright 2013 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
session_start();
include_once "templates/base.php";

/************************************************
  Make an API request authenticated with a service
  account.
 ************************************************/
require_once realpath(dirname(__FILE__) . '/../src/Google/autoload.php');

/************************************************
  ATTENTION: Fill in these values! You can get
  them by creating a new Service Account in the
  API console. Be sure to store the key file
  somewhere you can get to it - though in real
  operations you'd want to make sure it wasn't
  accessible from the webserver!
  The name is the email address value provided
  as part of the service account (not your
  address!)
  Make sure the Compute Engine API is enabled on this
  account as well, or the call will fail.
 ************************************************/
$client_id = '13807574021-v122hrf7pruoid4qkiq07vbakneh30ff.apps.googleusercontent.com'; //Client ID
$service_account_name = '13807574021-v122hrf7pruoid4qkiq07vbakneh30ff@developer.gserviceaccount.com'; //Email Address
$key_file_location = 'gce-demo.p12'; //key.p12

echo pageHeader("Google Compute Engine Example");
if (strpos($client_id, "googleusercontent") == false
    || !strlen($service_account_name)
    || !strlen($key_file_location)) {
  echo missingServiceAccountDetailsWarning();
  exit;
}

$client = new Google_Client();
$client->setApplicationName("Compute_Engine_Example");
$service = new Google_Service_Compute($client);

/************************************************
  If we have an access token, we can carry on.
  Otherwise, we'll get one with the help of an
  assertion credential. In other examples the list
  of scopes was managed by the Client, but here
  we have to list them manually. We also supply
  the service account
 ************************************************/
if (isset($_SESSION['service_token'])) {
  $client->setAccessToken($_SESSION['service_token']);
}
$key = file_get_contents($key_file_location);
$cred = new Google_Auth_AssertionCredentials(
    $service_account_name,
    array('https://www.googleapis.com/auth/cloud-platform',
        'https://www.googleapis.com/auth/compute'),
    $key
);
$client->setAssertionCredentials($cred);
if ($client->getAuth()->isAccessTokenExpired()) {
  $client->getAuth()->refreshTokenWithAssertion($cred);
}
$access_token = $client->getAccessToken();
$_SESSION['service_token'] = $access_token;

/************************************************
  We're just going to make the same call 
 ************************************************/

$project = "cit-bigdata-demos";
$zone = "us-central1-c";
$instance = "demo-instance-2";
#$gce_instance = new Google_Service_Compute_Instance(); 
#$service->instances->insert($project,$zone,$gce_intance);

$url = "https://www.googleapis.com/compute/v1/projects/$project/zones/$zone/instances";
$postBody = "{\"disks\":[{\"type\":\"PERSISTENT\",\"boot\":true,\"mode\":\"READ_WRITE\",\"deviceName\":\"$instance\",\"autoDelete\":true,\"initializeParams\":{\"sourceImage\":\"https:\/\/www.googleapis.com\/compute\/v1\/projects\/debian-cloud\/global\/images\/backports-debian-7-wheezy-v20150423\",\"diskType\":\"https:\/\/www.googleapis.com\/compute\/v1\/projects\/cit-bigdata-demos\/zones\/us-central1-c\/diskTypes\/pd-standard\"}}],\"networkInterfaces\":[{\"network\":\"https:\/\/www.googleapis.com\/compute\/v1\/projects\/cit-bigdata-demos\/global\/networks\/default\",\"accessConfigs\":[{\"name\":\"External NAT\",\"type\":\"ONE_TO_ONE_NAT\"}]}],\"metadata\":{\"items\":[]},\"tags\":{\"items\":[]},\"zone\":\"https:\/\/www.googleapis.com\/compute\/v1\/projects\/cit-bigdata-demos\/zones\/us-central1-c\",\"canIpForward\":false,\"scheduling\":{\"preemptible\":false,\"automaticRestart\":true,\"onHostMaintenance\":\"MIGRATE\"},\"name\":\"demo-instance\",\"machineType\":\"https:\/\/www.googleapis.com\/compute\/v1\/projects\/cit-bigdata-demos\/zones\/us-central1-c\/machineTypes\/n1-standard-1\",\"serviceAccounts\":[{\"email\":\"default\",\"scopes\":[\"https:\/\/www.googleapis.com\/auth\/devstorage.read_only\",\"https:\/\/www.googleapis.com\/auth\/logging.write\"]}]}";    
#$headers = array('Content-Type' => 'application/json;charset=UTF-8', 'Authorization' => 'Bearer ' .$access_token);
$headers = array('Content-Type' => 'application/json;charset=UTF-8');
$method = 'POST';
$request = new Google_Http_Request($url, $method, $headers, $postBody);
#$rest = new Google_Http_REST();
#$rest->execute($client,$request);
#$client->execute($request);
$resp = $client->getAuth()->authenticatedRequest($request);

echo "<pre>";
print_r($resp);
#print_r($service->instances->get($project,$zone,$instance));
echo "</pre>";

echo pageFooter(__FILE__);

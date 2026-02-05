<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("CONFIG_2.php");   // Make sure API_KEY and COMPANY_KEY are defined here
include("WEBLIB.php");

$url = "https://api.mandbox.com/apitest/v1/contact.php?action=view";

$weblib = new WebLib();

$params = array(
    "fullname" => "NEW NAME 2",
    "address" => "NEW ADDRESS 2",
    "record_id" => 331
);

// Send the request
$weblib->requestURL($url, $params);

// Get the raw response
echo "RESULT: " . $weblib->getRawResponse();

// Example: get a specific field from the JSON response
echo "<br>Details: " . $weblib->getKeyValue("contact_no", "data");
?>
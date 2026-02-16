<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/CONFIG_2.php";   // API_KEY and COMPANY_KEY
require_once __DIR__ . "/WEBLIB.php";

$url = "https://api.mandbox.com/apitest/v1/contact.php?action=add";

$weblib = new WebLib();

$params = array(
    "fullname" => "NEW NAME 2",
    "address" => "NEW ADDRESS 2",
    "record_id" => 331
);

// Send the request
$weblib->requestURL($url, $params);

// Get the raw response
$response = $weblib->getRawResponse();
echo "RESULT: " . $response;

// Decode JSON response
$data = json_decode($response, true);

// Check if request was successful
if ($data && isset($data['status']) && $data['status'] === 'success') {
    
    echo "<br><strong>Contact Added Successfully!</strong>";
    
    // Get specific field from response
    $contactNo = $weblib->getKeyValue("contact_no", "data");
    echo "<br>Contact No: " . $contactNo;

} else {

    echo "<br><strong>Failed to add contact.</strong>";
    
    // Show error message if available
    if (isset($data['message'])) {
        echo "<br>Error: " . $data['message'];
    } else {
        echo "<br>Unexpected response from API.";
    }
}
?> 
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/CONFIG_2.php";   // API_KEY and COMPANY_KEY
require_once __DIR__ . "/WEBLIB.php";

$weblib = new WebLib();

// VIEW: Get all contacts
$url= "https://api.mandbox.com/apitest/v1/contact.php?action=view";

// // search
// $search = $_GET['search'] ?? '';

// // per page
// $perPage = $_GET['limit'] ?? 5;

// // current page
// $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$params = [
    "show_all" => "0",
    "record_limit" => "5",
    "page" => "1",
    "search_key" => "sample"
];

// Send the request
$weblib->requestURL($url, $params);

// Get the raw response
$response = $weblib->getRawResponse();
echo "RESULT: " . $response;

// Decode JSON response
$data = json_decode($response, true);

?>


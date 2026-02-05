<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("CONFIG_2.php");   // API_KEY and COMPANY_KEY
include("WEBLIB.php");

$url = "https://api.mandbox.com/apitest/v1/contact.php?action=view";

$weblib = new WebLib();

// FIXED PARAMETERS for viewing the contact list
$params = array(
    "record_id" => "",  // leave empty to get all contacts
    "limit"     => 50,  // number of records per page
    "page"      => 1
);

// Send the request
$weblib->requestURL($url, $params);

// Get the response data
$dataList = $weblib->resultData(); // This is an array of contacts

// Check if data exists
if(!empty($dataList)) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Address</th>
            <th>Contact No</th>
          </tr>";

    foreach($dataList as $data) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($data->id ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($data->fullname ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($data->address ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($data->contact_no ?? '') . "</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "No records found.";
}
?>

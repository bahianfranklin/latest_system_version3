<?php
include("CONFIG_2.php");
include("WEBLIB.php");

$weblib = new WebLib();
$action = $_GET['action'] ?? '';

$params = $_POST;

switch ($action) {

    case 'add':
        $url = "https://api.mandbox.com/apitest/v1/contact.php?action=add";
        $weblib->requestURL($url, $params);
        echo $weblib->message() ?: "Contact added successfully!";
        break;

    case 'edit':
        $url = "https://api.mandbox.com/apitest/v1/contact.php?action=update";
        $weblib->requestURL($url, $params);

        // Check if API returned a message
        $msg = $weblib->message() ?: "No response from API";

        echo $msg; // this will show "Record updated successfully" if the API actually updated it
        break;


    case 'delete':
        $url = "https://api.mandbox.com/apitest/v1/contact.php?action=delete";
        $weblib->requestURL($url, $params);
        echo $weblib->message() ?: "Contact deleted successfully!";
        break;


    default:
        echo "Invalid action.";
}

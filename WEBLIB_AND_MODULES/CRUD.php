<?php

require_once __DIR__ . "/CONFIG_2.php";   // API_KEY and COMPANY_KEY
require_once __DIR__ . "/WEBLIB.php";

$weblib = new WebLib();
$action = $_GET['action'] ?? '';
$params = $_POST;

function apiSuccess($weblib)
{
    $data = $weblib->resultData();

    if (isset($data[0]['status']) && $data[0]['status'] == "yes") {
        return true;
    }

    return false;
}

switch ($action) {

    case 'add':
        $url = "https://api.mandbox.com/apitest/v1/contact.php?action=add";
        $weblib->requestURL($url, $params);

        echo apiSuccess($weblib)
            ? "Contact added successfully!"
            : "Failed to add contact.";

        break;


    case 'edit':
        $url = "https://api.mandbox.com/apitest/v1/contact.php?action=update";
        $weblib->requestURL($url, $params);

        echo apiSuccess($weblib)
            ? "Contact updated successfully!"
            : "Failed to update contact.";

        break;


    case 'delete':
        $url = "https://api.mandbox.com/apitest/v1/contact.php?action=delete";
        $weblib->requestURL($url, $params);

        echo apiSuccess($weblib)
            ? "Contact deleted successfully!"
            : "Failed to delete contact.";

        break;


    default:
        echo "Invalid action.";
}

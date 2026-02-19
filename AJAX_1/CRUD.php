<?php
require_once __DIR__ . "/CONFIG_2.php";
require_once __DIR__ . "/WEBLIB.php";

$weblib = new WebLib();
$action = $_GET['action'] ?? '';

if ($action == 'add') {
    $weblib->requestURL("https://api.mandbox.com/apitest/v1/contact.php?action=add", $_POST);
    $status = $weblib->status();
    $message = $weblib->message();

    if ($status === 'ok') {
        echo "Contact added successfully";

    } else {
        echo $message ?? "Failed to add contact";
    }
}

elseif ($action == 'edit') {
    $weblib->requestURL("https://api.mandbox.com/apitest/v1/contact.php?action=update", $_POST);
    $status = $weblib->status();
    $message = $weblib->message();

    if ($status === 'ok') {
        echo "Contact updated successfully";
    } else {
        echo $message ?? "Failed to update contact";
    }
}

elseif ($action == 'delete') {
    $weblib->requestURL("https://api.mandbox.com/apitest/v1/contact.php?action=delete", ['record_id' => $_POST['id']]);
    
    $status = $weblib->status(); // returns "success" or "failed"
    $message = $weblib->message(); // returns the API message

    if ($status === 'ok') {
        echo "Contact deleted successfully";
    } else {
        echo $message ?? "Failed to delete contact";
    }
}
?>

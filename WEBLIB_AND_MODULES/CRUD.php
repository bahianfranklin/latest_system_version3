<?php
require_once __DIR__ . "/CONFIG_2.php";
require_once __DIR__ . "/WEBLIB.php";

$weblib = new WebLib();
$action = $_GET['action'] ?? '';
$params = $_POST;

switch ($action) {

    case 'add':
    case 'edit':
    case 'delete':

        $actionMap = [
            'add'    => 'add',
            'edit'   => 'update',
            'delete' => 'delete'
        ];

        $url = "https://api.mandbox.com/apitest/v1/contact.php?action=" . $actionMap[$action];

        // Call the API
        $weblib->requestURL($url, $params);

        // Get init list and safely take the first element
        $initList = $weblib->resultInit(); // could be array of objects or array of arrays
        $init = null;
        if (is_array($initList) && !empty($initList)) {
            $init = $initList[0];
        }

        // Extract fields regardless of object/array shape
        $status  = null;
        $output  = null;
        $message = null;
        $code    = null;

        if (is_array($init)) {
            $status  = $init['status']  ?? null;
            $output  = $init['output']  ?? null;   // in case other endpoints use 'output'
            $message = $init['message'] ?? null;
            $code    = $init['code']    ?? null;
        } elseif (is_object($init)) {
            $status  = $init->status  ?? null;
            $output  = $init->output  ?? null;     // in case other endpoints use 'output'
            $message = $init->message ?? null;
            $code    = $init->code    ?? null;
        }

        // Success if either status === "ok" OR output === "ok"
        $ok = ($status === 'ok') || ($output === 'ok');

        if ($ok) {
            if ($action === "add") {
                echo "Contact added successfully!" . (!empty($message) ? " {$message}" : "");
            } elseif ($action === "edit") {
                echo "Contact updated successfully!" . (!empty($message) ? " {$message}" : "");
            } else {
                echo "Contact deleted successfully!" . (!empty($message) ? " {$message}" : "");
            }
        } else {
            // Show helpful debug info if API did not confirm success
            echo "Operation failed.<br>";
            echo "<pre>";
            echo "Init:\n";
            print_r($init);
            echo "\nRaw:\n";
            echo $weblib->getRawResponse();
            echo "</pre>";
        }

        break;

    default:
        http_response_code(400);
        echo "Invalid action. Use ?action=add|edit|delete.";
        break;
}
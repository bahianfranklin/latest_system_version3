<?php
/* TEST API */

$url = "https://api.mandbox.com/apitest/v1/contact.php?action=view";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

// echo $response;
// exit;

$response=str_replace("<pre>","",$response); // remove <pre> tag if exist
$response=str_replace("</pre>","",$response); //echo $response;

$jsonDecoded = json_decode($response); //echo "<pre>"; print_r($jsonDecoded); echo "</pre>";

$jsonInit = $jsonDecoded->init;
$jsonData = $jsonDecoded->data;

echo "<pre>";
print_r($jsonInit);
echo "</pre>";

// echo "STATUS: " . $jsonInit[0]->status . "<br>";

// foreach($jsonData as $data) {
//     //echo "ID: " . $data->id . "<br>";
// }


?>
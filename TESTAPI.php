<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("WEBLIB.php");

$url = "https://api.mandbox.com/apitest/v1/contact.php?action=view";



$weblib = new WebLib();

$params=array("fullname"=>"NEW NAME 1", "address"=>"NEW ADDRESS 1", "record_id"=>331);

$weblib->requestURL($url,$params);

echo $weblib->getRawResponse();

//echo "RESULT: ".$weblib->status();

echo $weblib->getKeyValue("contact_no","data"); 
exit;
if($weblib->status()=='ok'){
    foreach($weblib->resultData() as $data){
        echo "ID: ".$data->id." : ".$data->fullname."<br/>";
    }
}else{
    echo "ERROR: ".$weblib->message();
}

$weblib=NULL;



/*$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

echo $response;
exit;

$response=str_replace("<pre>","",$response);
$response=str_replace("</pre>","",$response);

$jsonDecoded = json_decode($response);

$jsonInit = $jsonDecoded->init;
$jsonData = $jsonDecoded->data;

//echo "<pre>";
//print_r($jsonInit);
//echo "</pre>";

echo "STATUS: " . $jsonInit[0]->status . "<br>";

foreach($jsonData as $data) {
    //echo "ID: " . $data->id . "<br>";
}*/


?>
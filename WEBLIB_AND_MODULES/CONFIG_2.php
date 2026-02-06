<?php
/* API LINKS */


define("ENV", "DEV");
$serverIP = getHostByName(getHostName());
if (!filter_var($serverIP, FILTER_VALIDATE_IP) === false) {
    $host=$serverIP;
} else {
    $host="localhost";
}

if(ENV=="DEV"){  //Development
    //define("API", "https://api.mandbox.com/apiabicmicro/");
    //define('DOMAIN', "https://abicmicro.mandbox.com/");
    define('DOMAIN', "https://dev.alliedbankers.com.ph/partnersportal/");
    define("API", "https://devapi.alliedbankers.com.ph/apiabicmicro/");
    //define('DOMAIN', "https://".$host."/abicmicro/");
    
    define('GW_URL','http://test.dragonpay.ph/Pay.aspx?');
    define('MERCHANT_ID','ALLIEDBANKERS');
    define('MERCHANT_PASSWORD','jFFr1b3kCltT7Sp');
}else{
    define("API", "https://api.alliedbankers.com.ph/apiabicmicro/");
    define('DOMAIN', "https://partners.alliedbankers.com.ph/");
    
    define('GW_URL','https://gw.dragonpay.ph/Pay.aspx?');
    define('MERCHANT_ID','ALLIEDBANKERS');
    define('MERCHANT_PASSWORD','T1CNnN5jDHZgRnO');
}

define("API_KEY", "Ra8wJFRXunDEdO7w9Zb5S0UNNdb8TQQdRzwZc4Cb3BVlbQRMZTDQqFiq4nv3cpNROKsbqPNxz61");
define("COMPANY_KEY", "ugoshOdCShHMOT0RTNz9rXnAWNFhPmJnJUpjR57hmE7Y6");

$subFolder="";
define('SITE_URL', DOMAIN.$subFolder);

$api_ver = "v1/";
define("API_URL", API.$api_ver);

define('API_URL_SESSION',	     API_URL."session/");
define('API_URL_AUDITTRAIL',	 API_URL."audittrail/");
define('API_URL_ACCOUNTTYPES',	 API_URL."accounttypes/");
define('API_URL_ACCOUNTS',	     API_URL."accounts/");
define('API_URL_PARTNERS',	     API_URL."partners/");
define('API_URL_GENERIC',	     API_URL."generic/");
define('API_URL_LOCATION',	     API_URL."location/");
define('API_URL_PRODUCTS',	     API_URL."products/");
define('API_URL_VEHICLEMAKE',    API_URL."vehiclemake/");
define('API_URL_VEHICLESERIES',	 API_URL."vehicleseries/");
define('API_URL_VEHICLEEDITION', API_URL."vehicleedition/");
define('API_URL_FUELTYPE',       API_URL."fueltype/");
define('API_URL_BODYTYPE',       API_URL."bodytype/");
define('API_URL_PISTONDISPLACEMENT',API_URL."pistondisplacement/");
define('API_URL_YEARMODEL',      API_URL."vehiclemodel/");
define('API_URL_TRANSMISSIONTYPE',API_URL."transmissiontype/");
define('API_URL_DOCUMENTSERIES', API_URL."documentseries/");
define('API_URL_DOCUMENTTYPE',	 API_URL."documenttype/");
define('API_URL_PREMIUMRATES',	 API_URL."premiumrates/");
define('API_URL_LINE',	         API_URL."line/");
define('API_URL_SUBLINE',	     API_URL."subline/");
define('API_URL_PERIL',	         API_URL."peril/");
define('API_URL_DUETAXES',	     API_URL."duetaxes/");
define('API_URL_DUETAXESRATE',	 API_URL."duetaxesrate/");
define('API_URL_MVTYPE',         API_URL."mvtype/");
define('API_URL_CTPLPREMIUMTYPE',API_URL."ctplpremiumtype/");
define('API_URL_PERILPREMIUMS',	 API_URL."perilpremiums/");
define('API_URL_PACKAGERATE',	 API_URL."packagerate/");
define('API_URL_PARTNERPRODUCTS',API_URL."partnerproducts/");
define('API_URL_REPLENISHMENT',	 API_URL."walletreplenishment/");
define('API_URL_TRANSACTION',	 API_URL."transaction/");
define('API_URL_BILLING',	     API_URL."billing/");
define('API_URL_REPORTS',	     API_URL."reports/");
define('API_URL_CLIENTS',	     API_URL."clients/");
define('API_URL_PAPLANRATES',	 API_URL."paplanrates/");
define('API_URL_BANKACCOUNTS',	 API_URL."bankaccounts/");
define('API_URL_SETTINGS',       API_URL."settings/");
define('API_URL_COMMISSION',     API_URL."commission/");
define('API_URL_PROMOCODE',      API_URL."promocode/");
define('API_URL_PALAWAN',        API_URL."palawanbranches/");
define('API_URL_MSP',            API_URL."msp/");
define('API_URL_PNBDATA',        API_URL."pnbdata/");
define('API_URL_REFERRER',       API_URL."referrer/");
define('API_URL_BILLSPAYMENT',   API_URL."billspayment/");
define('API_URL_MODULEACTION',   API_URL."moduleaction/");
define('API_URL_CGL',	         API_URL."cgl/");

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

date_default_timezone_set("Asia/Manila");
set_time_limit(7200);
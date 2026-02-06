<?php
/*

Description: 		iSpark Web Library Class
Author:             Rhaymand F. Tatlonghari
Email:              tatlonghari.rhaymand@mandbox.com
Company: 			Iisaac Technologies
Website:			www.mandbox.com

Date Created: 		May 28, 2016
Time Created: 		09:01 PM

Date Last Updated:	May 28, 2016
Time Last Created:	09:01 PM

*/

require_once __DIR__ . "/CONFIG_2.php";   // API_KEY and COMPANY_KEY

class WebLib{
    
    private $p_rawResponse;
    
    public function requestURL($url, $params, $accessToken="", $userID=""){
        
        $urltopost = $url;
        $keyParams=array("api_key"=>API_KEY,
                         "company_key"=>COMPANY_KEY,
                         "user_id"=>$userID,
                         "access_token"=>$accessToken,
                         "client_ip"=>$_SERVER['REMOTE_ADDR']
                        );
                        
        $datatopost=array_merge($params,$keyParams);
    	
        if(is_array($datatopost) == true)
        {
            foreach($datatopost as $key => $value)
            {
                if(strpos($value, '@') === 0)
                {
                    $filename = ltrim($value, '@');
                    $datatopost[$key] = new CURLFile($filename);
                }
            }
        }
        
    	$ch = curl_init ($urltopost);
    	curl_setopt ($ch, CURLOPT_POST, true);
    	curl_setopt ($ch, CURLOPT_POSTFIELDS, $datatopost);
    	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    	curl_setopt($ch, CURLOPT_POSTREDIR, 3);
    	$returndata = curl_exec ($ch); 

        $this->p_rawResponse = $returndata;
        
    }
    
    public function setJsonData($jsonRawData){
        $this->p_rawResponse = $jsonRawData;
    }
    
    public function getRawResponse(){ //getJSONData
        return $this->p_rawResponse;
    }
    
    public function getJSONDecode(){ //getJSONDecode
        
        return $this->decodeJSON(); 
    }
    
    private function decodeJSON(){
        
        $trim1 = str_replace("<pre>", "", $this->p_rawResponse);
		$trim2 = str_replace("</pre>", "", $trim1);
        
        //$getJSONDecode = json_decode(stripslashes($trim2));
		$getJSONDecode = json_decode($trim2);
        
		return $getJSONDecode;
        
    }
    
    public function status(){ 
       
       $status="no"; 
        
       $data = $this->getJSONDecode(); 
        
	   if(count($data->init) > 0){  
    		foreach($data->init as $init){
    			$status = $init->status;
    		}
        }
		
		return $status;
	}
    
    public function recordCount(){
        
        $data = $this->getJSONDecode();
        
		foreach($data->init as $init){
			$record_count = $init->record_count;
		}
        
		return $record_count;
	}
    
    public function code(){ 
        
        $data = $this->getJSONDecode();
        
		foreach($data->init as $init){
			$message = $init->code;
		}
		
		return $message;
	}
    
    
    public function message(){ 

	$message = "yes";
        
        $data = $this->getJSONDecode();
        
        if(is_array($data->init)){
    		foreach($data->init as $init){
    			$message = $init->message;
    		}
        }
		
		return $message;
	}
    
    public function resultInit(){
        
        $data = $this->getJSONDecode();
        
		return $data->init;
	}
    
    public function resultData(){
        
        $data = $this->getJSONDecode();
        
		return $data->data;
	}
    
    public function getKeyValue($key,$jsonTag='init'){
        
        $data = $this->getJSONDecode();
        
		foreach($data->$jsonTag as $d){
			$val = $d->$key;
		}
		
		return $val;
	}
    
    public function isAccessTokenValid(){
		
		$data = $this->getJSONDecode();
        $code = "";
        
		$result = false;
        
		if(is_array($data->init)){ 
    		foreach($data->init as $init){
    			$code = $init->code;
    		}
        }
		
		if($code=='701' || $code == '702'){
			$result=false;
		}else{
			$result=true;
		}
		
		return $result;
	}
    
    public function getPageCount(){
        
        $data = $this->getJSONDecode();
        
		foreach($data->init as $init){
			$page_count = $init->page_count;
		}
        
		return $page_count;
	}
    
    public function getTotalRecord(){
        
        $data = $this->getJSONDecode();
        
		foreach($data->init as $init){
			$page_count = $init->total_records;
		}
        
		return $page_count;
	}
    
    public function getRecordLimit(){
        
        $data = $this->getJSONDecode();
        
		foreach($data->init as $init){
			$page_count = $init->page_limit;
		}
        
		return $page_count;
	}
    
}

?>
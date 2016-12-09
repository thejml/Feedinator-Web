<?php

class RESTInterface {
    public $baseURL  = "127.0.0.1";
    public $port     = 2375;
    public $proxy    = "";
    private $debug   = false;
    private $headers = array('Content-type: application/json');

    public function __construct($ip = "127.0.0.1", $port = 2375, $proxy = '', $debug = false, $extraHeaders=array()) {
        $this->baseURL = $ip;
        $this->port    = $port;
        $this->proxy   = $proxy;
        $this->debug   = $debug;
        if (count($extraHeaders)>0) {
            foreach ($extraHeaders as $header) {
                $this->headers[] = $header;
            }
        }
    }
    
    public function hostInfo() {
        return array("hostname"=>$this->baseURL,"port"=>$this->port,"proxy"=>$this->proxy);
    }
    
    public function post($uri,$payload,$silent = true) {
         $defaults = array( 
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_POST => 1, 
            CURLOPT_PORT => $this->port,
            CURLOPT_HEADER => 0, 
            CURLOPT_URL => $this->baseURL.$uri, 
            CURLOPT_FRESH_CONNECT => 1, 
            CURLOPT_RETURNTRANSFER => 1, 
            CURLOPT_FORBID_REUSE => 1, 
            CURLOPT_TIMEOUT => 4, 
            CURLOPT_SSL_VERIFYPEER => false
        ); 
        
        if (count($payload)>0) {
            $defaults[CURLOPT_POSTFIELDS]=json_encode($payload);
        }

        if ($this->proxy!="") { 
            $defaults[CURLOPT_PROXY]=$this->proxy;
        }

        $ch = curl_init(); 
        curl_setopt_array($ch, $defaults); 
        
        $result = curl_exec($ch);
        
        if (!$silent && curl_error($ch) !== false) {
            if ($this->debug) { trigger_error(curl_error($ch)); }
    	    echo $result;
        } 
        curl_close($ch); 

        return $result;
    }

    // Just like post, but for multiple servers at one time.
    public function postMulti($uri, $payload, $ipList, $silent = true) {
	$curlResources = array();
	$num = 0;

        $defaults = array( 
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_POST => 1, 
            CURLOPT_PORT => $this->port,
            CURLOPT_HEADER => 0, 
            CURLOPT_FRESH_CONNECT => 1, 
            CURLOPT_RETURNTRANSFER => 1, 
            CURLOPT_FORBID_REUSE => 1, 
            CURLOPT_TIMEOUT => 4, 
            CURLOPT_SSL_VERIFYPEER => false
        ); 

        if (count($payload)>0) {
            $defaults[CURLOPT_POSTFIELDS]=json_encode($payload);
        }

        if ($this->proxy!="") { 
            $defaults[CURLOPT_PROXY]=$this->proxy;
        }

	// Create cURL resources
	foreach ($ipList as $ip) {
            $curlResources[$num] = curl_init();

            // Set URL and other appropriate options
            curl_setopt_array($curlResources[$num], $defaults); 
            curl_setopt($curlResources[$num], CURLOPT_URL, "http://".$ip.$uri);
            $num++;
	}

        // Create the multiple cURL handle
        $multiHandle = curl_multi_init();

        // Add the handles
	for ($i=0; $i<count($curlResources); $i++) {
            curl_multi_add_handle($multiHandle,$curlResources[$i]);
	}

        $active = null;
        // Execute the handles
        do {
            $mrc = curl_multi_exec($multiHandle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($multiHandle) != -1) {
                do {
                    $mrc = curl_multi_exec($multiHandle, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        // Close the handles

	for ($i=0; $i<count($curlResources); $i++) {
            curl_multi_remove_handle($multiHandle, $curlResources[$i]);
        }

        curl_multi_close($multiHandle);
    }

    public function get($uri,$decode=true) { 
        $url=$this->baseURL.$uri; 
	if ($this->debug) { echo "Curling: ".$url."\n"; }
        $defaults = array( 
            CURLOPT_HEADER => 0, 
            CURLOPT_URL => $url, 
            CURLOPT_PORT => $this->port,
            CURLOPT_FRESH_CONNECT => 1, 
            CURLOPT_RETURNTRANSFER => 1, 
            CURLOPT_FORBID_REUSE => 1, 
            CURLOPT_TIMEOUT => 4, 
            CURLOPT_SSL_VERIFYPEER => false
        ); 
    
        if ($this->proxy!="") { $defaults[CURLOPT_PROXY]=$this->proxy; }
    
        $ch = curl_init(); 
        curl_setopt_array($ch, $defaults); 

        $attempts=0;
        while( ( ! $result = curl_exec($ch) ) && $attempts<3 ) { $attempts++; }

        if (!$result) {
            if ($this->debug) { trigger_error(curl_error($ch)); }
	    return false; 
        } 
        curl_close($ch); 
	if (!$decode) { 
		return $result; 
	} else {
        	return json_decode($result,TRUE); 
	}
    }

    public function delete($uri, $json='')
    {
	$hostname=trim(exec("/bin/hostname"));
        $url="http://".$this->baseURL.":".$this->port.$uri."#".$hostname;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $result = json_decode($result);
        curl_close($ch);

        return $result; 
    }

}

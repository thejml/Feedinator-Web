<?php
include_once("restInterface.inc");

$feedinatorURL ='https://api.thejml.info/';
$proxy         = "";
$debug         = false;
$extraHeaders  = array();
$rest = new RESTInterface($feedinatorURL, 443, $proxy, $debug, $extraHeaders);

function addFeed($url, array $post = NULL, array $options = array()) 
{ 
    if ($post==NULL) { return false; }
    $url.='addfeed';
    $defaults = array( 
        CURLOPT_POST => 1, 
        CURLOPT_HEADER => 0, 
        CURLOPT_URL => $url, 
        CURLOPT_FRESH_CONNECT => 1, 
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_FORBID_REUSE => 1, 
        CURLOPT_TIMEOUT => 4, 
        CURLOPT_POSTFIELDS => http_build_query($post),
	CURLOPT_SSL_VERIFYPEER => false
    ); 

    $ch = curl_init(); 
    curl_setopt_array($ch, ($options + $defaults)); 
    if( ! $result = curl_exec($ch)) 
    { 
        trigger_error(curl_error($ch)); 
    } 
    curl_close($ch); 
    return $result; 
} 

function sanitize($item) {
	return trim($item);
}

function deFeedURL($url) {
	return preg_replace('/^feed:\/\//','http://',$url);
}

function findValidFavIcon($baseURL) { 
	// Add in logic to try for .png, .gif, or .jpg
	return trim($baseURL,'/').'/favicon.ico';
}

function decodeFeed($url,$debug=false) {
	$feedInfo     = array();

	$URLExp       = explode("/",$url);
	$hostname     = $URLExp[2];
	unset ($URLExp[0]); unset($URLExp[1]); unset($URLExp[2]);  //Get rid of 'http:', '', and hostname
	$URI          = implode("/",$URLExp);
	$proxy        = "";
	$extraHeaders = array();
	$feedREST     = new RESTInterface($hostname, 80, $proxy, $debug, $extraHeaders);
	$dataHTML     = $feedREST->get('/'.$URI,false);
	if ($debug) { var_dump($dataHTML); }
	$xml          = simplexml_load_string($dataHTML);
	$json         = json_encode($xml);
	$decodedArray = json_decode($json,TRUE);

	// Now we have info, let's fillin the blanks.
	$feedInfo['title']       = $decodedArray['channel']['title'];
	$feedInfo['description'] = $decodedArray['channel']['description'];
	$feedInfo['icon']        = findValidFavIcon('https://'.$hostname);

//http://rss.cnn.com/rss/cnn_topstories.rss
	if (is_array($feedInfo['title']) || $feedInfo['title']=="") { 
		$feedInfo['title'] = $hostname;
	}

	return $feedInfo;
}

//{"category":1,"dateAdded":1364420689,"feedid":1,"image":"","lastDispatch":1391222035892,"lastUpdate":1391222035892,"lastUpdatedBy":"kanzaki","personal":0,"timeOffset":0,"title":"CNN World News","type":0,"url":"http://rss.cnn.com/rss/cnn_topstories.rss","who":1}
$feedInfo    = decodeFeed(sanitize($_POST['feedURL']),false);
$cat         = sanitize($_POST['feedCategory']);
$title       = $feedInfo['title'];
$description = $feedInfo['description'];
$image       = $feedInfo['icon'];
$url         = deFeedURL(sanitize($_POST['feedURL']));
$who         = 0; // Should figure out who's posting.

$newFeed     = array(	"category"=>$cat,
			"dateAdded"=>time(),
			"image"=>$image,
			"lastDispatch"=>time()*1000000,
			"lastUpdate"=>time()*1000000,
			"lastUpdatedBy"=>$who,
			"personal"=>0,
			"timeOffset"=>0,
			"title"=>$title,
			"description"=>$description,
			"type"=>0,
			"url"=>$url,
			"who"=>0
	       );

print_r($newFeed);

$addFeedReturn=addFeed($feedinatorURL,$newFeed);

if ($addFeedReturn=='OK') {
	echo "Thanks for submitting a new feed! We'll pull that data in within the next few minutes.";
} else { echo $addFeedReturn; }


?>

<?php
$feedinatorURL='http://feedinator-thejml.rhcloud.com';

function addFeed($url, array $post = NULL, array $options = array()) 
{ 
    if ($post==NULL) { return false; }

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

//{"category":1,"dateAdded":1364420689,"feedid":1,"image":"","lastDispatch":1391222035892,"lastUpdate":1391222035892,"lastUpdatedBy":"kanzaki","personal":0,"timeOffset":0,"title":"CNN World News","type":0,"url":"http://rss.cnn.com/rss/cnn_topstories.rss","who":1}
$cat=sanitize($_POST['feedCategory']);
$title=sanitize($_POST['feedTitle']);
$url=deFeedURL(sanitize($_POST['feedURL']));

$newFeed=array("category"=>$cat,"dateAdded"=>time(),"image"=>"","lastDispatch"=>time()*1000000,"lastUpdate"=>time()*1000000,"lastUpdatedBy"=>"","personal"=>0,"timeOffset"=>0,"title"=>$title,"type"=>0,"url"=>$url,"who"=>0);

$addFeedReturn=addFeed($feedinatorURL,$newFeed);
if ($addFeedReturn=='OK') {
	echo "Thanks for submitting a new feed! We'll pull that data in within the next few minutes.";
} else { echo $addFeedReturn; }


?>

#!/usr/bin/php
<?php

function normalizeFeed($rss) {
	if (!is_array($rss)) { return ""; }
	foreach ($rss as &$rs) { foreach ($rs as &$s) { $s=htmlspecialchars(str_replace("'","&#39",strip_tags($s))); } } //Normalize things. PHP 5 style.
	$elements=array('title','link','description','author','category','enclosure','guid','pubDate');
	foreach($rss as &$rs) {
		foreach ($elements as $el) {
			if (!isset($rs[$el])) { 
				$rs[$el]=""; 
			}
		}
	}
	return $rss;
}

function fixDate($str,$timeOffset) {
	return date("Y-m-d H:i:s",strtotime($str)+($timeOffset*3600));
}

function cleanGoogleNews($str) {
	$s2ex=explode("&amp;",$str);
	foreach ($s2ex as $s2) {
		if (substr($s2,0,4)=="url=") {
			return substr($s2,4);
		}
	}
	return $str;
}

function pushToFeedinator($data) {
	$timestamp=gettimeofday();
	$dataToPush=array();
	$dataToPush['feedid']		= $data['feedid'];
	$dataToPush['title']		= addslashes($data['title']);
	$dataToPush['url']		= urlencode($data['link']);
	$dataToPush['image']		= (isset($data['image'])?$data['image']:"");
	$dataToPush['pubdateseconds']	= $data['pubdateseconds'];
	$dataToPush['timeaggregated']	= $timestamp['sec'].$timestamp['usec'];
	$dataToPush['category']		= (isset($data['category'])?$data['category']:0);
	$dataToPush['description']	= addslashes($data['description']);
	$dataToPush['author']		= addslashes($data['author']);
	$dataToPush['uuid']		= $data['uuid'];
	$dataToPush['guid']		= $data['guid'];
	$data_string = json_encode($dataToPush);                                                                                   

	$ch = curl_init('http://feedinator-thejml.rhcloud.com/story/'.$dataToPush['uuid']);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
	    'Content-Type: application/json',                                                                                
	    'Content-Length: ' . strlen($data_string))                                                                       
	);                                                                                                                   
	curl_setopt($ch, CURLOPT_TIMEOUT, 4); 
 
	$result = curl_exec($ch);

}

function insertFeed($rss,$id,$timeOffset=0,$debug=FALSE,$usedUuids) {
	$rsscount=0; $storycount=0; $updatecount=0; $errorcount=0;
	$type='xml';
if ($debug) { print_r($usedUuids); }
	$rss=normalizeFeed($rss);
	//echo "Inserting FeedID = ".$id."\n\n";
	foreach ($rss as $rs) {
		if ($type='xml') {
			$date = isset($rs['dc:date']) ? $rs['dc:date'] : $rs['pubDate'];
			if (($rs['title'] != "Advertisement:") && ($rs['title'] != "Sponsored By:") && (trim($rs['title']) != "")) { 
				$rs['guid']=md5($rs['link']); 
				$rs['description']=preg_replace('/&amp;#39/i','&#39;',$rs['description']);
				$rs['title']=preg_replace('/&amp;#39/i','&#39;',$rs['title']);
				$uuid=md5(trim(strtolower($rs['title']))); // Just title?
		//		echo $rs['title'].' '.$rs['link'].' '.$date.' '.$uuid."\n";
				$timestamp=gettimeofday(); 
				$pds=strtotime(fixDate($date,$timeOffset)).$timestamp['usec'];
				$rs['pubdateseconds']=$pds;
				$rs['uuid']=$uuid;
				$rs['pubdate']=fixDate($date,$timeOffset);
				unset ($rs['pubDate']);
				unset ($rs['enclosure']);
				// Deal with google news links...
				$rs['link']=cleanGoogleNews($rs['link']);
				$rs['feedid']=$id;
				$story=array('body'=>$rs,'type'=>'story_type','index'=>'stories','id'=>$rs['guid']);
				// Index the Story!
				$storycount++;
				if (!in_array($rs['uuid'],$usedUuids)) {
					pushToFeedinator($rs);
					$updatecount++;
					if ($debug) { echo "Inserted ".$rs['pubdate']." - ".$rs['title']."\n"; }
				} else { if ($debug) { echo "NOT Inserting ".$rs['pubdate']." - ".$rs['title']."\n"; } }
			}
			$rsscount++;
		} else if ($type='dc') {

		} else { echo "Unknown Feed Type"; return FALSE; } //What the hell IS this feed?
	}
	echo "Processed ".$storycount." stories, inserted ".$updatecount."\n";
	return array('stories'=>$storycount,'updates'=>$updatecount,'rss'=>$rsscount,'errors'=>$errorcount);
}

// See if a URL exists, return true or false.
// Not Used...
function urlExists($url) {
    // Version 4.x supported
    $handle   = curl_init($url);
    if (false === $handle)
    {
        return false;
    }
    curl_setopt($handle, CURLOPT_HEADER, false);
    curl_setopt($handle, CURLOPT_FAILONERROR, true);  // this works
    curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") ); // request as if Firefox   
    curl_setopt($handle, CURLOPT_NOBODY, true);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
    $connectable = curl_exec($handle);
    curl_close($handle);  
    return $connectable;
}

function getFeed($url,$debug=FALSE) {
	$defaults = array( 
		CURLOPT_HEADER => 0, 
		CURLOPT_URL => $url, 
		CURLOPT_FRESH_CONNECT => 1, 
		CURLOPT_RETURNTRANSFER => 1, 
		CURLOPT_FORBID_REUSE => 1, 
		CURLOPT_TIMEOUT => 4, 
		CURLOPT_SSL_VERIFYPEER => false
	); 

//	if ($this->proxy!="") { $defaults[CURLOPT_PROXY]=$this->proxy; }
    
	$ch = curl_init(); 
	curl_setopt_array($ch, $defaults); 

	$attempts=0;
	while( ( ! $result = curl_exec($ch) ) && $attempts<3 ) { $attempts++; }

	if (!$result) {
		echo $result;
		trigger_error(curl_error($ch));
		return false; 
	} 
	curl_close($ch); 	
	return $result;
}



function Lexicate($url,$debug=FALSE) {
	if (strlen($url)<8) { return false; }
	//Save this to allow sub-domain links and file links (change http://domain/asdf/stuff.html to http://domain)
//	$ctx = stream_context_create(array('http' => array( 'timeout' => 200 ) ) ); 
	echo $url."\n";
//	$file = file_get_contents($url,'FILE_TEXT',$ctx);
	$file = getFeed($url);
	$p = xml_parser_create();
    	xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
    	xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($p, $file, $vals, $index);
	xml_parser_free($p);
	if ($debug==TRUE) {
		echo "\n\nVals:\n";
			print_r($vals);
		echo "\n\nIndex:\n";
			print_r($index);
	}

	$out=array();
	$itemnum=0;
	if (isset($vals[0]['attributes']['xmlns']) && $vals[0]['attributes']['xmlns']=="http://www.w3.org/2005/Atom") { 
//		echo 'Reg Feed!'; 
	        if (!isset($index['entry'])) { return ""; }
	        for ($it=0;$it<count($index['entry']);$it+=2) {
	                $item =$index['entry'][$it];
	                $item2=$index['entry'][$it+1];
	                if (($item<$item2) ){ // && (isset($vals[$item]['value']))) {
	                        $out[$itemnum]=array();
	                        for($i=$item+1;$i<$item2;$i++){
	                                if (isset($vals[$i]['attributes']['href'])) {
	                                        $value=$vals[$i]['attributes']['href'];
	                                        $tag=$vals[$i]['tag'];
	                                        $out[$itemnum][$tag]=$value;
	                                } else if (isset($vals[$i]['value'])) {
	                                        $value=$vals[$i]['value'];
	                                        $tag=$vals[$i]['tag'];
	                                        $out[$itemnum][$tag]=$value;
	                                }
	                        }
	                }
	                $itemnum++;
	        }
		for ($i=0;$i<count($out);$i++) {
	                if (isset($out[$i]['updated']) && !isset($out[$i]['pubDate'])) { $out[$i]['pubDate']=$out[$i]['updated']; }
	        }

	} else {
//		echo "Normal Feed";
		if (!isset($index['item'])) { return ""; }
		for ($it=0;$it<count($index['item']);$it+=2) {
			$item =$index['item'][$it];
			$item2=$index['item'][$it+1];
			if (($item<$item2) ){ // && (isset($vals[$item]['value']))) {
				$out[$itemnum]=array();
				for($i=$item+1;$i<$item2;$i++){
					if (isset($vals[$i]['value'])) {
						$value=$vals[$i]['value'];
						$tag=$vals[$i]['tag'];
						$out[$itemnum][$tag]=$value;
					}
				}
			}
			$itemnum++;
		}
	}
	return $out;
//	fclose($log);
}

function falseOrValue($value,$alt=-1) { 
	if ($value===FALSE) { return $alt; } else { return $value; }
}

function cleanDescription(&$feed,$isHTML=FALSE,$debug=FALSE) {
	for($t=0;$t<count($feed);$t++) {
		if (isset($feed[$t]['summary'])) { $feed[$t]['description']=$feed[$t]['summary']; } //(Some feeds use summary instead, none (as of yet) use both...)
		if (isset($feed[$t]['description'])) {
			if ($debug==TRUE) { echo 'was: '.$feed[$t]['description']."\n"; }
			if ( ($isHTML==true) || ($feed[$t]['description'][0]=="<") ) {
				$feed[$t]['description']=strip_tags($feed[$t]['description'],"<i><b>");
			} else {
				$feed[$t]['description']=strip_tags($feed[$t]['description'],'');
				if ($debug==TRUE) { echo "intermediate: ".$feed[$t]['description']; }
			}
			$feed[$t]['description']=substr($feed[$t]['description'],0,falseOrValue(stripos($feed[$t]['description'],"read more"),strlen($feed[$t]['description'])));
			$feed[$t]['description']=substr($feed[$t]['description'],0,falseOrValue(stripos($feed[$t]['description'],"read on for"),strlen($feed[$t]['description'])));
			$feed[$t]['description']=substr($feed[$t]['description'],0,falseOrValue(stripos($feed[$t]['description'],"Read the comm"),strlen($feed[$t]['description'])));
			$feed[$t]['description']=substr($feed[$t]['description'],0,falseOrValue(stripos($feed[$t]['description'],"Continue reading"),strlen($feed[$t]['description'])));
			$feed[$t]['description']=substr($feed[$t]['description'],0,falseOrValue(stripos($feed[$t]['description'],"Please see our"),strlen($feed[$t]['description'])));
			if ($debug==TRUE) { echo 'now: '.$feed[$t]['description']."\n"; }
		}
	} 
}

function updateStoryCounts(&$ar,$ar2) {
	foreach ($ar2 as $ar2k=>$ar2v) { 
		if (isset($ar[$ar2k])) { $ar[$ar2k]+=$ar2v; } else { $ar[$ar2k]=$ar2v; }
	}
	return $ar;
}

function getNextFeed($url,$hostname) {
    $defaults = array( 
        CURLOPT_POST => 0, 
        CURLOPT_HEADER => 0, 
        CURLOPT_URL => trim($url.$hostname), 
        CURLOPT_FRESH_CONNECT => 1, 
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_FORBID_REUSE => 1, 
        CURLOPT_TIMEOUT => 4, 
    ); 

    $ch = curl_init(); 
    curl_setopt_array($ch, $defaults); 
    if( ! $result = curl_exec($ch)) 
    { 
        trigger_error(curl_error($ch)); 
    } 
    curl_close($ch); 
    return $result; 

}

function flattenUUIDs($ar) {
	$out=array();
	for($i=0;$i<count($ar);$i++) {
		array_push($out,$ar[$i]['uuid']);
	}
	return $out;
}

function main($args) {
	$hostname=shell_exec("/bin/hostname");
	$debug=(isset($args[2])?$args[2]:"");
	$i=0; $feedsToProcess=(isset($args[1])?$args[1]:15);
	$url="http://feedinator-thejml.rhcloud.com/dispatch/";
	$storiesaggr=array();
	if ($debug=='true') { echo "Processing ".$feedsToProcess."\n"; }
	while ($feed=json_decode(getNextFeed($url,$hostname),TRUE)) {
		if (!isset($feed['info'])) {
			return false;
		}
		$i++;
		$info=$feed['info'];
		$uuids=flattenUUIDs($feed['uuids']);
		if ($debug=='true') { echo "Processing: ".trim($info['title'])."\n"; }
		if (isset($info['url']) && trim($info['url'])!="");
		$rss=Lexicate(trim($info['url'])); 
		if ($rss!="") {
 			cleanDescription($rss,(isset($info['isHTML']) && ($info['isHTML']=='t')),($debug=='true') );
			updateStoryCounts($storiesaggr,insertFeed($rss,$info['feedid'],$info['timeOffset'],($debug=='true'),$uuids));
		}	
		$jo=array();
		if ($debug) { echo "Stories Aggrigated:\n"; print_r($storiesaggr); echo "\n"; } 
//		sleep(20); 
		if ($i==$feedsToProcess) { exit;
			ZendJobQueue::setCurrentJobStatus(ZendJobQueue::OK);
		}
	}

	print_r($storiesaggr);
	// Everything went well
} 

main($_GET);

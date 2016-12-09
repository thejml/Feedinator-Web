<?php

$data=array();
$jo=array();
$dbx="";

/* Memcache stores:
 39-85: Array
 (
     [TimeAggregated] => 1357852204
     [ID] => 5146738
     [Title] => Agreed, Baby Pandas Are Cute. But Why?
     [Description] => Xiao Liwu made his public debut Thursday at the San Diego Zoo. As fans crowded around the exhibit, hoping to catch a glimpse of the 5-month-old giant panda cub, we asked the question that perhaps needs no asking. Scientists offer some clues.
     [FeedID] => 39
     [pubDate] => 2013-01-10 15:45:00
     [Category] => 
 )
*/

/*"items": [
	{
//		"row":"0",
//		"num_fields":"13",
//		"id":"25210749",		"id_bubble":"8",
		"temp":"4",			"temp_bubble":"1",*
		"windspeed":"7",		"windspeed_bubble":"1",
		"gustspeed":"0",		"gustspeed_bubble":"1",
		"winddir":"360",		"winddir_bubble":"3",
		"barometer":"30.33",		"barometer_bubble":"5",
//		"time":"1360464098",		"time_bubble":"10",
		"visibility":"10",		"visibility_bubble":"2",
		"icao":"KORF",			"icao_bubble":"4",
		"feelslike":"2",		"feelslike_bubble":"1",
		"clouds":"CLRN",		"clouds_bubble":"4",
		"humidity":"56",		"humidity_bubble":"2",
		"dewpoint":"-4", 		"dewpoint_bubble":"2"
	}]
})
{"clouds":[{"Desc":"Scattered","Intensity":2,"Type":"SCT","Alt":15000}],"precipitation":[],"temp":{"Temp":-3,"DewPoint":-5,"RelativeHumidity":85.99,"HeatIndex":0,"WindChill":-5.8539199426041},"location":"KORD","gps":{"lat":36.8,"long":-76.2},"visibility":"10","wind":{"windkts":"06","windmph":6.9046767,"dir":"140","variable":0,"gust":0},"pressure":30.13,"maxCloudCover":{"Desc":"Scattered","Intensity":2,"Type":"SCT","Alt":15000},"maxPrecip":[],"icon":"SCTN","iconText":"Scattered","metar":"KORD 100151 14006KT 10SM SCT150 M03\/M05 A3013 RMK AO2 SLP213 T10281050 $"}

*/

function cmp($a, $b) {
    if ($a['pds'] == $b['pds']) {
        return 0;
    }
    return ($a['pds'] < $b['pds']) ? 1 : -1;
}

function cmp_pds($a, $b) {
    if ($a['pubdateseconds'] == $b['pubdateseconds']) {
        return 0;
    }
    return ($a['pubdateseconds'] < $b['pubdateseconds']) ? 1 : -1;
}

function weather($js) {
	$out=array();
	$out['temp']=$js['temp']['Temp'];
	$out['temp_bubble']=strlen($out['temp']);
	$out['windspeed']=round($js['wind']['windmph'],0);
	$out['gustspeed']=round($gs['wind']['gust'],0);
	$out['winddir']=$js['wind']['dir'];
	$out['barometer']=$js['pressure'];
	$out['visibility']=$js['visibility'];
	$out['icao']=$js['location'];
	$out['feelslike']=($js['temp']['WindChill']==0)?$js['temp']['HeatIndex']:$js['temp']['WindChill'];
	$out['clouds']=$js['icon'];
	$out['humidity']=$js['temp']['RelativeHumidity'];
	$out['dewpoint']=$js['temp']['DewPoint'];
	return $out;
}

function first($a,$m,$dropKeys) {
	$max=(count($a)>$m)?$m:count($a);
	$out=array();	$i=0;
	foreach ($a as $d) { 
		if ($i>$max) { 
			return $out; 
		} else {
			if ($dropKeys) { 
				$out[$i]=$d;
			} else { $out[$t]=$d; }
			$i++; 
		}
		
	}
}

$data=array();
$maxItems=250;

# This is for weather
#$memcache_obj = memcache_connect('memcache.thejml.info', 11211);
#if (isset($_GET['w']) && $_GET['w']==1 && isset($_GET['location'])) { //XXX This needs to do checking against location!
#	echo json_encode(weather(json_decode($memcache_obj->get($_GET['location']),TRUE)));
#	return;
#}

$jsonStories=file_get_contents("http://feedinator-thejml.rhcloud.com/current");
$data=json_decode($jsonStories,TRUE);
// gets us {"_id":"532c7936704496c38fed02ee","category":"","feedid":"40","image":"","pubdateseconds":1395398316512205,"title":"$149.95 - Fujifilm FinePix XP60 16.4 Megapixel Compact Camera - Green","uuid":"e03e6fdcfb4bccd657537e5e84f1f566"},...
//print_r($currentStories);
//echo $jsonStories;
/* this used to parse each category worth of stories, add them to one array, and then sort by date.
for ($i=0;$i<70;$i++) {
	$ja=json_decode($memcache_obj->get($i),TRUE);
	if (is_array($ja)) {
		foreach ($ja as $o) {
			array_push($data,$o);
		}
	}
}
 */

// We should fix the end point, but let's make sure this works first.
usort($data,'cmp_pds');

// We could grab the person's feed subs and then unset the non-matching stories, or pull the matching ones into a new array...

$curTime=time();
$i=0;
$max=(count($data)>$maxItems)?$maxItems:count($data);

if (!isset($_GET['json'])) {
	echo '<html><head><link type="text/css" rel="stylesheet" href="/css/nitrogen.css" /><title>JML Continuum - Mobile</title></head><body><ul>';
	foreach ($data as $d) { 
		if ($i>$max) { continue; } else { $i++; }
		echo "<li><div class='listingDiv' style='background-position: 0px ".($d['feedid']*(-32))."'>".
			"&nbsp;<a target='_blank' href='http://x.thejml.info:789/".$d['id']."&t=f' class='storyHeading'>".$d['title']."</a>".
			"<div style='font-size: 10px;'>".round(($curTime-$d['timeaggregated'])/60,0)." minutes ago</div>".
			"</div></li>\n"; 
	}
	echo "</body></html>";
} else {
	if (isset($_GET['c'])) { echo "\n".$_GET['c']."({\n\"items\": \n"; }
		echo json_encode($data);
	if (isset($_GET['c'])) { echo " \n})"; }
	exit;
}
	
?>

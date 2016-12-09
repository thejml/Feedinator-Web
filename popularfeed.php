<?php
//include_once("stdpage.php");

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
function disregardedWords() { 
	return array('to','a','the','of','and','&','+','=','or','for','in','on','at','for','is','be','-','--','from','/','into','it','up','its',"it's","it&#39;s",'after','may','more','june','all','video','mobile','times','journal','news','street','today','of','to','–',
			'such','says','new','them','they','us','out','an','are','your',"you're",'there','their','','with','this','as','watch:','watch','by','you','off','has','that','(reuters)','will',
			 'have','was','said','but','his','hers','him','her','she','he','who','not','writes','than','over','about','some','could','been','which','one','first','how',
			 'can','could','what','...','two','three','four','five','six','seven','eight','nine','ten','second','secondly','were','years','no','data','people','we','when',
			 'news','other','get','back','use','&amp;mdash;','$amp;mdash','had','monday','tuesday','wednesday','thursday','friday','saturday','sunday','july','august','september','october',
			 'november','december','january','feburary','april','apr','feb','jan','dec','nov','oct','sept','aug','jul','jun','mar','mon','tue','wed','thurs','fri','sat','sun',
			 'like','if','would','hundred','thousand','million','hundreds','thousands','millions','billions','billion','1','2','3','4','5','6','7','8','9','0','10','11','12','13','14','15','16','17','18','19','20','21','--','&',
			 'our','see','edt.','edt','still','just','world','originally','appeared','any','wed.','tue.','thurs.','company','fri.','mon.','sun.','sat.','edit',"don't",
			 'a','b','c,','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','do','york','times','now','later','soon',
			 'around','way','should','take','through','former','number','say','found','latest','well','since','much','used','until','being','many','#:','—',
			 'made','own','going','few','percent','group','those','articles&amp;nbsp;&amp;raquo;','set','only','',' ','look','day','plans','articles','(ap)','bank','top','bottom','os','charge','debit','special','today','tomorrow','yesterday','talks','show','my','including','both','big','between','complex','where','end','users','part','game','part','team','then',
			 'north','south','east','west','so','time','engadget','2010','2011','2012','2013','2014','2015','2016','also','make','last','articles&ampnbsp;&ampraquo;','articles&nbsp;&raquo;','&mdash','&ndash','most','least','even','odd','state','county','country','district','city','town','suburb','deal','culpable','able','car','cars','road','race','speed','yes','no','too','why','old','new','name','rate','talk','preview','moves','move','begin','begins','cut','wax','red','blue','green','yellow','black','white','orange','began','wins','loose','loses','lose','free','ease','did','little','man','woman','women','men','died','die','kill','killed','due','work','eye','eyes','ride','rode','fake','finding','home','case','come',
			 'articles&nbsp;&raquo','articles&ampnbsp;&ampraquo','articles&amp;nbsp;&amp;raquo','kt','kts','2011','2010','2012','2009','forecast','national','near','tropical','gmt','cart','model','color','price','faces','under','over','street','st','str','final','while','death','phone','brother','sister','first','device','best','worst','down','up','here','there','full','half','empty','week','next','month','year','day');
}


function cmp($a, $b) {
    if ($a['pds'] == $b['pds']) {
        return 0;
    }
    return ($a['pds'] < $b['pds']) ? 1 : -1;
}

function cmpImp($a, $b) {
//    if ($a['title'] == $b['title']) {
//        return 0;
//    }
    return (strcmp($a['imp'],$b['imp']));
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

function importantWords($title) {
	$badWords=disregardedWords();
	$c=array();
	foreach ($badWords as $b) { array_push($bw,'/'.$b.'/'); array_push($c,' '); }
	echo count($badWords)."-".count($c)."-";
	$im=preg_replace($bw,$c,$title);
	echo $title."==".$im."+\n";
	return $im;
}

function importantWords2($title) {
	$title=preg_replace("/[^A-Za-z- ]/", '', $title);
	$words=explode(" ",$title);
	$badWords=disregardedWords();
	$out=array();
	$punctuation=array("'",'"',"!",'.',',',';',':','/','\\','?','<','>','+','=','-','_','@','#','$','%','^','&','*','(',')');
	foreach ($words as $word) { 
		if (!in_array(strtolower($word),$badWords)) { array_push($out,$word); }
	}
	asort($out);
	$outStr=implode(" ",$out);
	return $outStr;
}

$data=array();
$maxItems=250;

//$memcache_obj = memcache_connect('memcache.thejml.info', 11211);
//for ($i=0;$i<70;$i++) {
//	$ja=json_decode($memcache_obj->get($i),TRUE);
	$ja=json_decode(file_get_contents("http://feedinator-thejml.rhcloud.com/current"),TRUE);
	if (is_array($ja)) {
		foreach ($ja as $o) {
//			if ($argv[1]=='imp') { 
//				$o['imp']=importantWords($o['title']); 
				$o['imp']=importantWords2($o['title']); 
			$data[$o['uuid']]=$o;
		}
	}
//}
//usort($data,'cmpImp');
$Words=array();
foreach ($data as $d ) {
	if ($d['pubdateseconds']>time()-43200) {
		$wx = explode(" ",$d['imp']);
		foreach ($wx as $w) { 
			if (!isset($Words[$w])) { 
				$Words[$w]=array(); 
			} 
			array_push($Words[$w],$d['uuid']); 
		}
	}
}

$outarray=array();
$outjson="";
$popnumber=0;
foreach ($data as $d) { 
		$counts=array();
		$ex=explode(" ",$d['imp']); 
		foreach ($ex as $e) { # echo $e." ".print_r($Words[$e],TRUE);
			foreach ($Words[$e] as $id) { 
				if (!isset($counts[$id])) { $counts[$id]=1; } else { $counts[$id]++; }
			}
		}
		$d['related']=array();
		asort ($counts);
		$out=""; $words=$d['imp'];
		foreach ($counts as $c=>$b) { 
			if (($b>1) && isset($data[$c])){
				array_push($d['related'],$data[$c]);
				//$out.=$c." - $b - <a href='https://x.thejml.info/".$c."&t=f'>".$data[$c]['title']."</a>\n"; 
				// Once grouped, don't allow grouping again
				unset($data[$c]); 
				//unset($counts[$c]);
			} 
		}
		$outarray[$popnumber]=$d;
		$popnumber++;
/*		if ($out!="") {
			echo $words."\n".$out."\n --- \n";
}*/
}
	#foreach ($Words as $w=>$d) { echo $w.": ".print_r($d,TRUE); }
	#foreach($data as $d) { if ($d['pubdateseconds']>time()-43200) { echo $d['imp']."\n"; } }
echo json_encode($outarray);
#print_r($data);
exit;
usort($data,'cmp');
$curTime=time();
$i=0;
$max=(count($data)>$maxItems)?$maxItems:count($data);

if (isset($_GET['c'])) { echo "\n".$_GET['c']."({\n\"items\": \n"; }
//echo json_encode(first($data,$maxItems,TRUE));
echo json_encode($outarray);
if (isset($_GET['c'])) { echo " \n})"; }
	
?>

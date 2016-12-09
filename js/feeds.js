function drawUpdate(x,hilite,colors) {
	var count,liclass;
	var c; c=0;
	var title,cl;
	var tasklink,fblink,twitterlink;
	fblink=""; twitterlink="";
	var pubdate,idate,tempDate,lasttime,adate;
	lasttime='future';
	$("#stories").html("");
	cl='class="stories"'
    $.each(x, function(i,item){
	title=item.title.replace(/&amp;#39/g,"'");
	pubdate=item.pubdateseconds; 
	link="http://x.thejml.info/s/"+item.uuid;
	item.description='';
	$("<tr "+cl+" id='item"+item.uuid+"' style='height: 32px;'/>").html(
//			'<td><div class=listingDiv style="background-position: 0px '+(item.feedid*-32+32)+'px; width: 32px;height:32px;"></div></td>'+
			'<td><a href="'+link+'" target=_blank class=storyHeading>'+title+'</a></td>'
			+'<td>'+(new Date(item.pubdateseconds).toLocaleTimeString())+'</td>'
		).appendTo("#stories");
	});
}

function drawUpdate2(x,hilite,colors) {
	var count,catClass;
	var c; c=0;
	var title,cl;
	var tasklink,fblink,twitterlink;
	fblink=""; twitterlink="";
	var pubdate,idate,tempDate,lasttime,adate;
	lasttime='future';
	$("#stories").html("");
	cl='storyCard'
    $.each(x, function(i,item){
	title=item.title.replace(/&amp;#39/g,"'");
	pubdate=item.pubdateseconds; 
	if (item.category=='') { catClass='NoCategory'; } else { catClass=item.category; }
	link="http://x.thejml.info/s/"+item.uuid;
	$('<div class="'+cl+'" id="item'+item.uuid+'" />').html(
//			'<td><div class=listingDiv style="background-position: 0px '+(item.feedid*-32+32)+'px; width: 32px;height:32px;"></div></td>'+
			'<div class="cardTime '+catClass+'">'+(new Date(item.pubdateseconds).toLocaleTimeString())+'</div>'+
			'<img class="cardIcon" src="'+item.image+'"></div><div class="cardHeading"><a href="'+link+'" target=_blank class=storyHeading>'+title+'</a></div>'+
			'<div class="cardExpander '+item.uuid+'-expand"><i class="fa fa-chevron-right" aria-hidden="true"></i></div>'+
			'<div class="cardSummary '+item.uuid+'-desc">'+item.description+'</div>'
		).appendTo("#stories");
	});
}

function updateFeeds() {
  var now=Math.round(new Date().getTime()/1000);
	$.getJSON("feed.php",  
	  {
		"n":Math.round(new Date().getTime()/1000)-7200,
		"o":"5",
		"l":"200",
		"json":"1"
	  },function(data){drawUpdate2(data,'',0)});
}

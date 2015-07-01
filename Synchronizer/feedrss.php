
/*
    (c) 2007-2008 Scriptol.com
    Reduced version of the PHP RSS Reader.
    http://www.scriptol.com/rss/rss-reader.php
    
    For extending the PHP FTP Synchronizer/
*/    

$RSS_Content = array();
$RSS_Channel = array();

function RSS_Tags($item)
{
		$y = array();
		$tnl = $item->getElementsByTagName("title");
		$tnl = $tnl->item(0);
		$title = $tnl->firstChild->data;

		$tnl = $item->getElementsByTagName("link");
		$tnl = $tnl->item(0);
		$link = $tnl->firstChild->data;

		$tnl = $item->getElementsByTagName("description");
		$tnl = $tnl->item(0);
		$description = $tnl->firstChild->data;

		$tnl = $item->getElementsByTagName("pubDate");
		$tnl = $tnl->item(0);
		$date = $tnl->firstChild->data;

		$y["title"] = $title;
		$y["link"] = $link;
		$y["description"] = $description;
		$y["date"] = $date;
		
		return $y;
}




function RSS_Retrieve($url)
{
	global $RSS_Content;

	$doc  = new DOMDocument();
	$doc->load($url);

	$channels = $doc->getElementsByTagName("channel");
	$RSS_Channel = RSS_Tags($channels[0])
	
	$RSS_Content = array();
	
	foreach($channels as $channel)
	{
		$items = $channel->getElementsByTagName("item");
		foreach($items as $item)
		{
			$y = RSS_Tags($item);	// get description of article, type 1
			array_push($RSS_Content, $y);
		}
		 
	}

}

// Taken from ARA Editor
// http://www.scriptol.com/rss/ara.php
// By Denis Sureau

function buildRSS($rssname, $channel, $urls)
{

  // processing channel

  $title = $channel["title"];
  $link =  $channel["link"];
  $desc = $channel["description"];
  $date = $channel["date"];

	$myfeed = new Ara();
	$title= stripslashes($title);
	$detected = mb_detect_encoding($title);
	$title = mb_convert_encoding($title, "UTF-8", $detected);
	
  $detected = mb_detect_encoding($desc);
  $desc = mb_convert_encoding($desc, "UTF-8", $detected);
  $desc = stripslashes($desc);
	$myfeed->ARAFeed($title, $link, $desc, $date);	

	// processing items
	
	foreach($urls as $oneurl)
	{

    $title = $oneurl["title"];
    $link = $oneurl["link"];
    $desc = $oneurl["description"];
    $date = $oneurl["date"];            

    $myfeed->ARAItem($title, $link, $desc, $date);		
	
	}


	$ximage = $myfeed->saveXML();
	$xf = fopen($rssname, "w");
	if($xf != false)
	{
		fwrite($xf, $ximage);
		fclose($xf);
	}
}



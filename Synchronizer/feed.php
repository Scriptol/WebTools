<?php
//
// Feed - Scriptol Library
// http://www.scriptol.com/compiler/
// Licence: LPGL
// Add a document to an RSS feed
// Created from the PHP RSS Reader
// http://www.scriptol.com/rss/rss-reader.php
//
// (c) 2008-2009 by Denis Sureau. Scriptol.com
//

include_once("feedrss.php");
include_once("linkcheck.php");
$FEEDNAME="/rss.xml";
$FEEDSIZE=15;
$RSSFEED=false;
function createFeed($title,$desc)
{
   $date=date();
   global $RSS_Channel;
   $RSS_Channel["title"]=$title;
   global $website;
   $RSS_Channel["link"]=$website;
   $RSS_Channel["description"]=$desc;
   $RSS_Channel["date"]=date();

   return;
}

function older($urls)
{
   $indice=0;
   $older="";
   if(count($urls)===1)
   {
      return 0;
   }
   for($i=0;$i<=count($urls);$i++)
   {
      $x=$urls[$i];
      $date=$x["date"];
      global $older;
      if($date>$older)
      {
         $indice=$i;
         $older=$date;
      }
   }
   return $indice;
}

function updateFeed($urls)
{
   global $FEEDNAME;
   $feed=RSS_RetrieveLinks($FEEDNAME);
   $fsize=count($feed);
   $usize=count($urls);
   global $FEEDSIZE;
   if($usize>=$FEEDSIZE)
   {
      $feed=array();

   }
   while(!empty($urls))
   {
      do
      {
         $i=older($urls);
         $x=$urls[$i];
         array_unshift($feed,$x);
         array_splice($urls,$i,$i-($i)+count($urls)*(($i<0)-($i<0))+1);
      } while(false);
   }
   $feed=array_slice($feed,0,$FEEDSIZE-(0)+count($feed)*($FEEDSIZE<0)+1);

   global $RSS_Channel;
   buildRSS($FEEDNAME,$RSS_Channel,$feed);
   return;
}


?>

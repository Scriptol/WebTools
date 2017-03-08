<?php
// Sitemap extension to PHP FTP Synchronizer
// (c) 2016 Denis Sureau
// www.scriptol.com
// Licence LGPL

// Format compatible with sitemap.org and all search engines
include_once("dom.php");
include_once("path.php");
$xmlmap=new DOMDocument();
$urlset=null;
$urlList=array();
$dateFormat="Y-m-d\\TH:i:sP";
$mapname="sitemap.xml";
$sitemapExtensions=array(".html",".php",".htm",".asp");
$mapremote="";
$mapInArray=array();
$mapSize=0;
function newMap()
{
   global $xmlmap;
   $xmlmap=new DOMDocument("1.0","UTF-8");
   global $urlset;
   $urlset=$xmlmap->createElement("urlset");
   $urlset->setAttribute("xmlns","http://www.google.com/schemas/sitemap/0.84");
   $urlset->setAttribute("xmlns:xsi","http://www.w3.org/2001/XMLSchema-instance");
   $urlset->setAttribute("xsi:schemaLocation","http://www.google.com/schemas/sitemap/0.84 http://www.google.com/schemas/sitemap/0.84/sitemap.xsd");
   $xmlmap->appendChild($urlset);
   return;
}

// Load sitemap, get urlset element.
// Or create a new map if not found.
function loadMap()
{
   global $xmlmap;
   $xmlmap->formatOutput=true;
   $xmlmap->preserveWhiteSpace=false;
   global $mapname;
   $e=$xmlmap->load($mapname);
   if($e===true)
   {
      $dnl=$xmlmap->getElementsByTagName("urlset");
      if($dnl->length>0)
      {
         global $urlset;
         $urlset=$dnl->item(0);
         return;
      }
   }
   newMap();
   return;
}

function saveMap()
{
   global $xmlmap;
   global $mapname;
   $xmlmap->save($mapname);
   return;
}

function XML2Array()
{
   global $xmlmap;
   $selection=$xmlmap->getElementsByTagName("loc");
   for($i=0;$i<$selection->length;$i++)
   {
      $n=$selection->item($i);
      global $mapInArray;
      array_push($mapInArray,$n->nodeValue);
   }
   global $mapSize;
   global $mapInArray;
   $mapSize=count($mapInArray);
   return;
}

function inMap($url)
{
   global $mapInArray;
   if(in_array($url,$mapInArray))
   {
      return true;
   }
   return false;
}

// Build an url tag
// Add to urlset or
// replace existing tag with same url,
// update date, keep frequency and priority
function addTag($url,$linktime)
{
   global $xmlmap;
   $urltag=$xmlmap->createElement("url");
   $actest=false;
   $priority="0.2";
   $frequency="monthly";
   if(inMap($url))
   {
      $dnl=$xmlmap->getElementsByTagName("loc");
      for($i=0;$i<$dnl->length;$i++)
      {
         $e=$dnl->item($i);
         if($e->nodeValue!=$url)
         {
            continue;
         }
         $oldtag=$e->parentNode;
         $fc=$oldtag->firstChild;
         while(($fc))
         {
            do
            {
               $name=$fc->nodeName;
               $val=$fc->nodeValue;
               if($name==="changeFreq")
               {
                  $frequency=$val;
               }
               if($name==="priority")
               {
                  $priority=$val;
               }
            } while(false);
            $fc=$fc->nextSibling;
         }
         global $urlset;
         $actest=$urlset->replaceChild($urltag,$oldtag);
         echo "Updated $url in sitemap.", "\n";
         break;
      }
   }
   else
   {
      global $urlset;
      $actest=$urlset->appendChild($urltag);
      echo "Added $url to sitemap.", "\n";
      global $mapSize;
      $mapSize+=1;
   }
   if($actest===false)
   {
      echo "Error, can't append url to urlset.", "\n";
      return;
   }
   $loctag=$xmlmap->createElement("loc",$url);
   $modtag=$xmlmap->createElement("lastmod",$linktime);
   $ftag=$xmlmap->createElement("changeFreq",$frequency);
   $ptag=$xmlmap->createElement("priority",$priority);
   $urltag->appendChild($loctag);
   $urltag->appendChild($modtag);
   $urltag->appendChild($ftag);
   $urltag->appendChild($ptag);
   return;
}

// Convert local to URL and to unix
function setURL($name)
{
   for($i=0;$i<strlen($name);$i++)
   {
      if($name{$i}==="\\")
      {
         $name{$i}="/";
      }
   }
   return $name;
}

function updateMap()
{
   global $urlList;
   if(count($urlList)<1)
   {
      return;
   }
   global $QUIET;
   if(!$QUIET)
   {
      echo "\nUpdating sitemap:", " ", count($urlList), " ", "links to add/update.", "\n";
   }
   loadMap();
   XML2Array();
   foreach($urlList as $url)
   {
      $url=setURL($url);
      if(!hasProtocol($url))
      {
         global $website;
         $url=Path::merge($website,$url);
      }
      global $dateFormat;
      $urlTime=date($dateFormat);
      addTag($url,$urlTime);
   }
   saveMap();
   if(!$QUIET)
   {
      global $mapSize;
      echo $mapSize, " ", "links in sitemap.", "\n";
   }
   return;
}

?>

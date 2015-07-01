<?php

//	Simple Map - XML, text or HTML Site Map  Generator
//	A free open source Scriptol program,  by (c) Denis G. Sureau
//	http://www.scriptol.com/
include_once("path.php");
include_once("libphp.php");
include_once("tools.php");
include_once("options.php");
include_once("url.php");
include_once("page.php");
$siteList=array();
$siteTime=array();
$checkedList=array();
$skipped=array();
$defaultList=array();
$pageText="";
$siteMap=array();
function output($t)
{
   global $siteMap;
   array_push($siteMap,$t);
   return;
}

function extractLink($off)
{
   $c="";
   $link="";
   global $pageText;
   while($off<strlen($pageText))
   {
      do
      {
         $c=$pageText{$off};
         if($c==='=')
         {
            break;
         }
         if($c===' ')
         {
            break;
         }
         if($c==="\"")
         {
            $off+=1;
            break 2;
         }
         if($c==='\'')
         {
            $off+=1;
            break 2;
         }
         break 2;
      } while(false);
      $off+=1;
   }
   while($off<strlen($pageText))
   {
      do
      {
         $c=$pageText{$off};
         if($c==='\'')
         {
            break 2;
         }
         if($c==="\"")
         {
            break 2;
         }
         if($c==='>')
         {
            break 2;
         }
         $link.=$c;
      } while(false);
      $off+=1;
   }
   return trim($link);
}

function inCheckedList($page)
{
   if(!hasProtocol($page))
   {
      $page=createLinkFromRelative($page);
   }
   global $checkedList;
   if(in_array($page,$checkedList))
   {
      return true;
   }
   return false;
}

function inSiteMap($page)
{
   if(!hasProtocol($page))
   {
      $page=createLinkFromRelative($page);
   }
   global $siteList;
   if(in_array($page,$siteList))
   {
      return true;
   }
   return false;
}

function addFileList($page)
{
   if(!hasProtocol($page))
   {
      $page=createLinkFromRelative($page);
   }
   global $checkedList;
   if(in_array($page,$checkedList))
   {
      return;
   }
   array_push($checkedList,$page);
   return;
}

function addLink($page,$lmod)
{
   $url="";
   $url=setURL($page);

   if(!hasProtocol($page))
   {
      global $siteURL;
      $url=Path::merge($siteURL,$url);
   }
   global $siteList;
   if(in_array($url,$siteList))
   {
      return false;
   }
   array_push($siteList,$url);
   global $LASTMOD;
   if($LASTMOD)
   {
      global $siteTime;
      global $dateFormat;
      $siteTime[$url]=date($dateFormat,intVal($lmod));
   }
   display("Added link $url");
   return true;
}

function isIndexable($fullpath)
{
   $tags=get_meta_tags($fullpath);
   $robots=$tags['robots'];
   if($robots==="")
   {
      return true;
   }
   if(stripos($robots,"noindex")!=false)
   {
      return false;
   }
   if(stripos($robots,"none")!=false)
   {
      return false;
   }
   return true;
}

function getLinks($page)
{
   $x=array();
   $x=file($page);
   global $DEBUG;
   if($DEBUG)
   {
      echo "Getting links from ", " ", getcwd(), " ", $page, "\n";
   }
   $pageLinks=array();
   reset($x);
   do
   {
      $x[key($x)]=rtrim(strval(current($x)));
   }
   while(!(next($x)===false));
   global $pageText;
   $pageText=implode("",$x);

   $offset=0;
   $srcoff=0;
   $shifting=0;
   while(true)
   {
      do
      {
         if($offset!=-1)
         {
            $offset=strpos($pageText,"href",$shifting);
         }
         if($srcoff!=-1)
         {
            $srcoff=strpos($pageText,"frame src",$shifting);
            if($srcoff===-1)
            {
               $srcoff=strpos($pageText,"frame src",$shifting);
            }
         }
         if($offset<1)
         {
            if($srcoff<1)
            {
               break 2;
            }
            $shifting=$srcoff+5;
         }
         else
         {
            if($srcoff<1)
            {
               $shifting=$offset;
            }
            else
            {
               if($offset<$srcoff)
               {
                  $shifting=$offset;
               }
               else
               {
                  $shifting=$srcoff+5;
               }
            }
         }
         $shifting+=4;

         $link=extractLink($shifting);
         $shifting+=strlen($link);

         if($link==="")
         {
            break;
         }
         if($link{0}==='#')
         {
            break;
         }
         global $siteList;
         if(in_array($link,$siteList))
         {
            break;
         }
         if(in_array($link,$pageLinks))
         {
            break;
         }
         if(!validExtension($link)&&!isDirectory($link))
         {
            break;
         }
         if(strstr($link,"../"))
         {
            display(" ../ such path is not valid, use absolute URL instead (in ".$link.").");
            break;
         }
         if(hasProtocol($link))
         {
            $host=getURL($link);
            global $siteURL;
            if($host===$siteURL)
            {
               array_push($pageLinks,$link);
            }
            break;
         }
         $realLink=$link;
         if(isDirectory($link))
         {
            $link=findDefault($link);

         }
         global $WINDOWS;
         if($WINDOWS)
         {
            $link=setWindows($link);
         }
         if(file_exists(getcwd()."/".$link))
         {
            array_push($pageLinks,$realLink);
         }
         else
         {
            global $VERBOSE;
            if($DEBUG||$VERBOSE)
            {
               echo "Broken link: $link not in", " ", getcwd(), "\n";
            }
         }
      } while(false);
   }
   return $pageLinks;
}

function scanPage($page)
{
   $counter=0;
   $fullpath="";
   $lmod=0;
   $link="";
   $linkBase="";
   $localDir="";
   $pageDir="";
   $temp="";
   $realPage="";
   addFileList($page);
   if(!parsable($page))
   {
      return 0;
   }
   if(hasProtocol($page))
   {
      $link=$page;
      if(!isInternal($page))
      {
         return 0;
      }
      $page=getLocal($page);
      $_I1=Path::splitFile($page);
      $localDir=reset($_I1);
      $page=next($_I1);
      global $localRoot;
      $localDir=Path::merge($localRoot,$localDir);
      $fullpath=Path::merge($localDir,$page);
   }
   else
   {
      $fullpath=Path::merge(getcwd(),$page);
      $link=createLinkFromName($page);

   }
   global $WINDOWS;
   if($WINDOWS)
   {
      $fullpath=setWindows($fullpath);
   }
   if(isDirectory($fullpath))
   {
      $fullpath=findDefault($fullpath);
      addFileList($fullpath);
   }
   if(!file_exists($fullpath))
   {
      display("   ".$fullpath." not found");
      return 0;
   }
   if(!isIndexable($fullpath))
   {
      display("    $fullpath is NOINDEX, skipped");
      return 0;
   }
   changeDir($localDir);
   global $DEBUG;
   global $VERBOSE;
   if($DEBUG||$VERBOSE)
   {
      echo "Scanning page: $fullpath", "\n";
   }
   $lmod=filemtime($fullpath);
   if(!addLink($link,$lmod))
   {
      return $counter;
   }
   $k="";
   $v="";
   $counter+=1;

   $dep=getLinks($fullpath);
   if($dep===array())
   {
      return $counter;
   }
   if($VERBOSE||$DEBUG)
   {
      echo count($dep), " ", "internal links found", "\n";
   }
   foreach($dep as $name)
   {
      if(!isInternal($name))
      {
         if($DEBUG)
         {
            echo "$name external, skipped.", "\n";
         }
         continue;
      }
      if(inSiteMap($name)===true)
      {
         continue;
      }
      if(isDirectory($link))
      {
         $temp=findDefault($name);
         global $defaultList;
         $defaultList[$temp]=$name;
         $name=$temp;
      }
      if(hasProtocol($name))
      {
         $pageDir=getcwd();
         $counter+=scanPage($name);
         changeDir($pageDir);
         continue;
      }
      if(Path::hasDir($name))
      {
         $ndir="";
         $filename="";
         $_I1=Path::splitFile($name);
         $ndir=reset($_I1);
         $filename=next($_I1);
         $pageDir=getcwd();
         changeDir($ndir);
         $counter+=scanPage($filename);
         changeDir($pageDir);
      }
      else
      {
         $counter+=scanPage($name);
      }
   }
   return $counter;
}

function buildTag($tagname,$value)
{
   if($value==="")
   {
      return;
   }
   output("     <".$tagname.">".$value."</".$tagname.">");
   return;
}

// From the list of files build now a xml file
function buildTheXmlFile()
{
   $name="";
   output("<?xml version=\"1.0\" encoding=\"UTF-8\"?>");
   output("<urlset xmlns=\"http://www.google.com/schemas/sitemap/0.84\" ");
   output("xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" ");
   output("xsi:schemaLocation=\"http://www.google.com/schemas/sitemap/0.84 ");
   output("http://www.google.com/schemas/sitemap/0.84/sitemap.xsd\">");
   global $siteList;
   foreach($siteList as $name)
   {
      output("  <url>");
      buildTag("loc",textToUTF8($name));
      global $PRIORITY;
      if($PRIORITY===true)
      {
         buildTag("priority",strval(0.2));
      }
      global $LASTMOD;
      global $LASTMODTIME;
      if(($LASTMOD===true)||($LASTMODTIME===true))
      {
         global $siteTime;
         $lmod=$siteTime[$name];
         if($lmod!="")
         {
            buildTag("lastmod",$lmod);
         }
      }
      global $FREQUENCY;
      if($FREQUENCY===true)
      {
         global $DEFAULT_FREQUENCY;
         buildTag("changefreq",$DEFAULT_FREQUENCY);
      }
      output("  </url>");
   }
   output("</urlset>");
   return;
}

$levelOffset=0;
// create a tag, content UTF-8 compatible
function buildLink($link)
{
   $linkpath="";
   $name="";
   output("<li>");
   $link=textToUTF8($link);
   $_I1=Path::splitFile($link);
   $linkpath=reset($_I1);
   $name=next($_I1);
   output("<a href=".$link.">".$name."</a>");
   output("</li>");
   return;
}

// extract dir
function openSubdir($dirname)
{
   output("<br>");
   output("<h4>");
   output($dirname);
   output("</h4>");
   output("<ul>");
   return;
}

function closeSubdir()
{
   output("</ul>");
   return;
}

function removeBase($linkpath,$base)
{
   $l=strlen($base);
   if($l<strlen($linkpath))
   {
      if(substr($linkpath,0,$l)===$base)
      {
         return substr($linkpath,$l+1);
      }
   }
   return $linkpath;
}

// test if the local part of the URL holds a directory
function hasDir($name)
{
   $l=strlen($name);
   if($l<2)
   {
      return false;
   }
   if(strpos($name,"/",1)!=false)
   {
      return true;
   }
   return false;
}

// get dir
function getMainDir($name)
{
   if(strlen($name)===0)
   {
      return "";
   }
   $i=strpos($name,"/");
   if($i<1)
   {
      return "";
   }
   return substr($name,0,$i);
}

function processDirs($base)
{
   $page="";
   $name="";
   $linkpath="";
   $empty=true;
   $currdir="";
   $thisdir="";
   global $siteList;
   for($i=0;$i<count($siteList);$i++)
   {
      if($siteList[$i]==="")
      {
         continue;
      }
      $page=$siteList[$i];
      $name=removeBase($page,$base);

      if(!Path::hasDir($name))
      {
         buildLink($page);
         $siteList[$i]="";
      }
      else
      {
         $empty=false;
      }
   }
   if($empty===true)
   {
      return;
   }
   global $siteList;
   for($i=0;$i<count($siteList);$i++)
   {
      if($siteList[$i]==="")
      {
         continue;
      }
      $page=$siteList[$i];
      $name=removeBase($page,$base);

      $thisdir=getMainDir($name);
      if($thisdir!=$currdir)
      {
         openSubdir($thisdir);
         processDirs($base."/".$thisdir);
         closeSubdir();
         $currdir=$thisdir;
      }
   }
   return;
}

// Scan list of links
// build a text array of page in sub-dirs
// make an entry for file in current directory
function processRoot()
{
   $urlpart="";
   $name="";
   $link="";
   output("<br>");
   output("<h1>");
   global $siteURL;
   output($siteURL);
   output("</h1>");
   global $siteList;
   reset($siteList);
   do
   {
      $link=current($siteList);
      $_I1=splitURL($link);
      $urlpart=reset($_I1);
      $name=next($_I1);
      if(Path::hasDir($name)!=true)
      {
         buildLink($link);
         $siteList[key($siteList)]="";
      }
   }
   while(!(next($siteList)===false));
   processDirs($siteURL);
   return;
}

// from the list of files
// build now a html page
// process file at the level
// then get first sub-dir, and loop
function buildTheHtmlTree()
{
   $name="";
   output("<html>");
   output("<head>");
   output("</head>");
   output("<body>");
   global $levelOffset;
   global $siteURL;
   $levelOffset=strlen($siteURL);
   processRoot();
   output("</body>");
   output("</html>");
   return;
}

// Main Simple Map function
function SimpleMap($num,$args)
{
   $changed=false;
   $counter=0;
   options($num,$args);
   echo "\n";
   global $version;
   echo $version, "\n";
   $currentPath=getcwd();
   global $basePath;
   if($currentPath!=$basePath)
   {
      global $VERBOSE;
      if($VERBOSE===true)
      {
         echo "Leaving", " ", $currentPath, "\n";
      }
      changeDir($basePath);
      $changed=true;
   }
   global $topname;
   $realTop=$topname;
   if(isDirectory($topname))
   {
      $topname=findDefault($topname);
   }
   if(!validExtension($topname))
   {
      echo "Filename missing or not a valid extension in local path.", "\n";
      return 0;
   }
   global $VERBOSE;
   if($VERBOSE)
   {
      global $siteURL;
      echo "Web adress:", " ", $siteURL, "\n";
   }
   global $localRoot;
   $localRoot=getcwd();

   $_L2=strlen($localRoot);
   for($__2 = 0; $__2 < $_L2; $__2++)
   {
      $t=$localRoot{$__2};
      if($t==="\\")
      {
         global $WINDOWS;
         $WINDOWS=true;
         break;
      }
   }
   $basePath="";
   $counter+=scanPage($topname);

   display("");
   global $siteList;
   sort($siteList);
   if($VERBOSE===true)
   {
      global $mapname;
      echo "Creating", " ", $mapname, "\n";
   }
   global $mapType;
   
   if($mapType===0)
   {
      buildTheXmlFile();
   }
   else
   {
      if($mapType===1)
      {
         global $mapname;
         file_put_contents($mapname,implode("\n",$siteList));
      }
   else
   {
      if($mapType===2)
      {
         buildTheHtmlTree();
      }
   }}
   if($mapType!=1)
   {
      global $mapname;
      $x=fopen($mapname,"w");
      global $siteMap;
      foreach($siteMap as $t)
      {
         fwrite($x,$t."\n");
      }
      fclose($x);
   }
   global $logfile;
   global $logname;
   file_put_contents($logname,implode("\n",$logfile));
   global $mapname;
   global $checkedList;
   display(strval(count($siteList))." pages added to ".$mapname.", ".strval(count($checkedList))." checked.");
   if($changed===true)
   {
      if($VERBOSE===true)
      {
         echo "now returning to original path", " ", $currentPath, "\n";
      }
      changeDir($currentPath);
   }
   return 0;
}


?>

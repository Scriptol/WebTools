<?php
//
// LinkCheck - Scriptol Library
// http://www.scriptol.com/compiler/
// Licence: LPGL
// Check an HTML page for broken links
//
// (c) 2008-2016 by Denis Sureau. Scriptol.com
//
include_once("dom.php");
include_once("path.php");
$CHECKLINKS=false;
$VERBOSE=false;
$QUIET=false;
$DEBUG=false;
$website="";
$source="";
$remotedir="";
$rdlength=0;
$broken=array("Links report:");
$pagesToCheck=array();
$brocount=0;
$extensions=array(".html",".php",".htm",".php5",".asp",".shtml",".dhtml",".jsp",".xhtml",".stm");
function httpAccess($url)
{
   $errno="";
   $errstr="";
   $page="";
   $site="";
   $fp=0;
   if(strlen($url)<8)
   {
      return 0;
   }
   if(strtolower(substr($url,0,7))!="http://")
   {
      return 0;
   }
   $l=intVal(strpos($url,"/",8));
   if($l<1)
   {
      $site=substr($url,7);
      $page="/";
   }
   else
   {
      $site=substr($url,7,$l-(7)+strlen($url)*($l<0));
      $page=substr($url,$l);
   }
   $fp=@fsockopen($site,80,$errno,$errstr,30);

   if($fp===false)
   {
      echo "Error $errstr ($errno) for $url viewed as site:$site page:$page", "\n";
      return 0;
   }
   $out="GET /$page HTTP/1.1\r\n";
   $out.="Host: $site\r\n";
   $out.="Connection: Close\r\n\r\n";

   fwrite($fp,$out);
   $content=fgets($fp);
   $code=trim(substr($content,9,4));
   fclose($fp);
   $icode=intVal(intval($code));
   if($icode===404)
   {
      $f=@fopen($url,"r");
      if($f!=false)
      {
         $cnt=@fread($f,128);
         if(strlen(trim($cnt))>0)
         {
            $icode=200;
         }
         fclose($f);
      }
   }
   return $icode;
}

function httpsAccess($url)
{
   if(strlen($url)<9)
   {
      return 0;
   }
   $headers=0;
   $code=0;
   
    if(function_exists("curl_init")) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_VERBOSE, false);
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_HEADER, true);
        curl_setopt($c, CURLOPT_NOBODY, true);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        $headers = curl_exec($c);
        $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);
    }
    else {
        return(0);
    }
    
   return intVal($code);
}

function convertUnix($src)
{
   return str_replace("\\","/",$src);
}

// remove trailing slash or backslash
function noSlash($pth)
{
   $c=substr($pth,-1);
   if(($c==="/")||($c==="\\"))
   {
      return substr($pth,0,-1);
   }
   return $pth;
}

function siteOffset($theurl)
{
   $offset=0;
   $offset=strpos($theurl,"http://");
   if($offset===false)
   {
      $offset=strpos($theurl,"ftp://");
      if($offset===false)
      {
         $offset=strpos($theurl,"https://");
         if($offset!=false)
         {
            $offset+=8;
         }
      }
      else
      {
         $offset+=6;
      }
   }
   else
   {
      $offset+=7;
   }
   return $offset;
}

// test if this is a remote  address (host included in the string)
function hasProtocol($theurl)
{
   $lowname=strtolower(ltrim($theurl));
   if(substr($lowname,0,7)==="http://")
   {
      return true;
   }
   if(substr($lowname,0,6)==="ftp://")
   {
      return true;
   }
   if(substr($lowname,0,8)==="https://")
   {
      return true;
   }
   return false;
}

function isHTML($name)
{
   $ext=Path::getExtension($name);
   global $extensions;
   if(in_array($ext,$extensions))
   {
      return true;
   }
   return false;
}

function linkchecker($page)
{
   $current=null;
   $elem=null;
   $xres=0;
   $link="";
   $base="";
   $resnum=0;
   $links=array();
   if(!isHTML($page))
   {
      return;
   }
   $page=convertUnix($page);
   global $source;
   $root=convertUnix($source);
   global $VERBOSE;
   if($VERBOSE)
   {
      echo "Scanning $page", "\n";
   }
   $_I1=Path::splitFile($page);
   $base=reset($_I1);
   $link=next($_I1);
   global $website;
   $base=str_replace($root,$website,$base);

   $d=new DOMDocument();
   
    @$xres = $d->loadHTMLFile($page);
    
   if($xres===false)
   {
      return;
   }
   $dnl=$d->getElementsByTagName("a");
   if($dnl->length===0)
   {
      return;
   }
   for($i=0;$i<=$dnl->length;$i++)
   {
      $current=$dnl->item($i);
      if($current===null)
      {
         continue;
      }
      $elem=$current;

      if($elem->hasAttribute("href"))
      {
         $link=$elem->getAttribute("href");
         if($link{0}==="#")
         {
            continue;
         }
         if($link{0}==="/")
         {
            $link=Path::merge($website,$link);
         }
         $p=intVal(strpos($link,"#",0));
         if($p!=0)
         {
            $link=substr($link,0,$p);
         }
         if(!hasProtocol($link))
         {
            if(strlen($link)>11)
            {
               if(substr($link,0,11)==="javascript:")
               {
                  global $DEBUG;
                  if($DEBUG)
                  {
                     echo "Skipped javascript.", "\n";
                  }
                  continue;
               }
            }
            if(strlen($link)>7)
            {
               if(substr($link,0,7)==="mailto:")
               {
                  global $DEBUG;
                  if($DEBUG)
                  {
                     echo "Skipped mailto.", "\n";
                  }
                  continue;
               }
            }
            $link=Path::merge($base,$link);
         }
         array_push($links,$link);
      }
   }
   if(count($links)===0)
   {
      return;
   }
   $HEADFLAG=true;
   foreach($links as $link)
   {
      if(substr($link,0,8)==="https://")
      {
         $resnum=httpsAccess($link);
      }
      else
      {
         $resnum=httpAccess($link);
      }
      if($resnum===200)
      {
         continue;
      }
      if($resnum===302)
      {
         continue;
      }
      if($HEADFLAG)
      {
         echo $page, "\n";
         echo str_repeat("-",strlen($page)), "\n";
         $HEADFLAG=false;
      }
      
      if($resnum===404)
      {
         echo "Broken $link", "\n";
         global $brocount;
         $brocount+=1;
      }
      else
      {
         if($resnum===301)
         {
            echo "Redirect $link", "\n";
         }
      else
      {
         echo "$resnum $link", "\n";
      }
      }
   }
   return;
}

function dispBroken()
{
   global $brocount;
   if($brocount===0)
   {
      return;
   }
   echo $brocount," broken link",($brocount>1?"s":""),".\n";
   return;
}

function linkCheckerDiffered($page)
{
   global $pagesToCheck;
   array_push($pagesToCheck,$page);
   return;
}

function differedCheck()
{
   global $CHECKLINKS;
   if(!$CHECKLINKS)
   {
      return;
   }
   echo "\nChecking links...", "\n";
   global $pagesToCheck;
   foreach($pagesToCheck as $t)
   {
      linkchecker($t);
   }
   return;
}

?>

<?php
include_once("path.php");
$WINDOWS=false;
$DEBUG=false;
$VERBOSE=false;
$siteURL="";
$mapType=0;
$mapname="";
$WEBADDRESS="";
$logname="";
$topname="";
$localRoot="";
$extensions=array("htm","html","php","php3","php4","php5","asp","shtml","dhtml","jsp","xhtml","sol","txt");
$pagext=array("htm","html","php","php3","php4","php5","asp","shtml","dhtml","jsp","xhtml");
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

function siteOffset($theurl)
{
   $lowname=strtolower(ltrim($theurl));
   if(substr($lowname,0,7)==="http://")
   {
      return 7;
   }
   if(substr($lowname,0,6)==="ftp://")
   {
      return 6;
   }
   if(substr($lowname,0,8)==="https://")
   {
      return 8;
   }
   return 0;
}

function splitURL($theurl)
{
   $offset=siteOffset($theurl);
   if($offset===false)
   {
      return array("",$theurl);
   }
   $offset=strpos($theurl,"/",$offset);
   if($offset===false)
   {
      return array($theurl,"");
   }
   return array(substr($theurl,0,$offset),substr($theurl,$offset+1));
}

function getURL($theurl)
{
   $offset=siteOffset($theurl);
   $offset=strpos($theurl,"/",$offset);
   if($offset===-1)
   {
      return $theurl;
   }
   return substr($theurl,0,$offset);
}

function findDefault($thedir)
{
   $url=null;
   global $pagext;
   foreach($pagext as $ext)
   {
      $url=$thedir."index.".$ext;
      if(file_exists($url))
      {
         return $url;
      }
   }
   global $pagext;
   foreach($pagext as $ext)
   {
      $url=$thedir."default.".$ext;
      if(file_exists($url))
      {
         return $url;
      }
   }
   global $pagext;
   foreach($pagext as $ext)
   {
      $url=$thedir."home.".$ext;
      if(file_exists($url))
      {
         return $url;
      }
   }
   global $pagext;
   foreach($pagext as $ext)
   {
      $url=$thedir."accueil.".$ext;
      if(file_exists($url))
      {
         return $url;
      }
   }
   $url=$thedir."index";
   if(file_exists($url))
   {
      return $url;
   }
   $url=$thedir."home";
   if(file_exists($url))
   {
      return $url;
   }
   $url=$thedir."accueil";
   if(file_exists($url))
   {
      return $url;
   }
   return $thedir;
}

// Retrieve the local path of the file from a full URL
// Remove the URL of the site (http://www.scriptol.com)
// For example:
// url is                 http://www.scriptol.com/ajax/index.php
// local dir is           c:\scriptol\
// the function returns   c:\scriptol\ajax\index.php
// returns also true as second value if it is interal to the site
// and false if it is a link to another website
function localPath($name)
{
   $p=siteOffset($name);
   if($p===0)
   {
      return array($name,true);
   }
   $name=substr($name,$p);

   global $siteURL;
   $l=strlen($siteURL);
   if(substr($siteURL,0,$l)==="/")
   {
      $l-=1;
   }
   $lowname=strtolower($name);
   if(substr($lowname,0,$l)===$siteURL)
   {
      if($lowname{$l}==="/")
      {
         $l+=1;
      }
      if(strlen($lowname)>$l)
      {
         global $localRoot;
         $name=Path::merge($localRoot,substr($name,$l));
         global $WINDOWS;
         if($WINDOWS)
         {
            $name=setWindows($name);
         }
      }
      else
      {
         $name="";
      }
      return array($name,true);
   }
   global $DEBUG;
   if($DEBUG)
   {
      echo $name, " ", "not in this website, ignored.", "\n";
   }
   return array("",false);
}

function isInternal($name)
{
   $p=siteOffset($name);
   if($p===0)
   {
      return true;
   }
   global $siteURL;
   if(strstr(strtolower($name),strtolower($siteURL)))
   {
      return true;
   }
   global $DEBUG;
   if($DEBUG)
   {
      echo $name, " ", "not in this website, ignored.", "\n";
   }
   return false;
}

function validExtension($fname)
{
   $node="";
   $ext="";
   $_I1=Path::splitExt($fname);
   $node=reset($_I1);
   $ext=next($_I1);

   global $extensions;
   if(in_array(strtolower($ext),$extensions))
   {
      return true;
   }
   return false;
}

function parsable($fname)
{
   $node="";
   $ext="";
   $_I1=Path::splitExt($fname);
   $node=reset($_I1);
   $ext=next($_I1);

   if(isDirectory($fname))
   {
      return true;
   }
   global $pagext;
   if(in_array(strtolower($ext),$pagext))
   {
      return true;
   }
   return false;
}

function getLocal($theurl)
{
   $slash=1;
   $offset=siteOffset($theurl);
   if($offset===0)
   {
      return $theurl;
   }
   global $siteURL;
   $len=strlen($siteURL);
   if(!hasProtocol($siteURL))
   {
      $len+=$offset;
   }
   $suffix=substr($siteURL,-1);
   if(($suffix==="/")||($suffix==="\\"))
   {
      $slash=0;
   }
   return substr($theurl,$len+$slash);
}

function createLinkFromName($name)
{
   $local=getcwd();
   global $localRoot;
   $l=strlen($localRoot);
   if(strlen($local)>$l)
   {
      if(strtolower($localRoot)===strtolower(substr($local,0,$l)))
      {
         $local=substr($local,$l+1);
      }
   }
   else
   {
      $local="";
   }
   global $siteURL;
   $p=$siteURL."/".$local;
   $suffix=substr($p,-1);
   if(($suffix==="/")||($suffix==="\\"))
   {
      return $p.$name;
   }
   return $p."/".$name;
}

function createLinkFromRelative($name)
{
   global $siteURL;
   $p=$siteURL."/".getcwd();
   $suffix=substr($p,-1);
   if(($suffix==="/")||($suffix==="\\"))
   {
      return $p.$name;
   }
   return $p."/".$name;
}

?>

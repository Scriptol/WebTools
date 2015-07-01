<?php

include_once("url.php");
// remove leaving slahs or backslash
function noSlash($pth)
{
   $c=substr($pth,-1);
   if(($c==="/")||($c==="\\"))
   {
      return substr($pth,0,-1);
   }
   return $pth;
}

function setWindows($name)
{
   for($i=0;$i<strlen($name);$i++)
   {
      if($name{$i}==="/")
      {
         $name{$i}="\\";
      }
   }
   return $name;
}

// convert local to URL and to unix
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

function textToUTF8($content)
{
   $content=str_replace("&","&amp;",$content);
   $content=str_replace("<","&lt;",$content);
   $content=str_replace(">","&gt;",$content);
   return $content;
}

// if drive letter in path, change drive
function changeDir($pth)
{
   if($pth==="")
   {
      return;
   }
   global $DEBUG;
   if($DEBUG)
   {
      echo "Moving to $pth", "\n";
   }
   $t=@chdir($pth);
   global $VERBOSE;
   if($DEBUG||$VERBOSE)
   {
      if($t===true)
      {
         echo "Now path is", " ", getcwd(), "\n";
      }
      else
      {
         echo "Error, enable to go to $pth from", " ", getcwd(), "\n";
      }
   }
   return;
}

// Check if the source ends with the string search
function endWith($source,$search)
{
   $last=substr($search,-1);
   if(($last==="/")||($last==="\\"))
   {
      $search=substr($search,0,-1);
   }
   $lsea=strlen($search);
   $lsrc=strlen($source);
   if($lsrc<$lsea)
   {
      return false;
   }
   if(substr($source,-$lsea)===$search)
   {
      return true;
   }
   return false;
}

// End with "\"
function isDirectory($source)
{
   if($source==="")
   {
      return true;
   }
   $last=substr($source,-1);
   if(($last==="/")||($last==="\\"))
   {
      return true;
   }
   return false;
}


?>

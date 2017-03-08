<?php

//  Path Class
//  Scriptol - (c) 2001-2016  D.G Sureau
//  www.scriptol.com
//  Licence: OSS
//  This is a set of static functions related to files in directory
//  The path separator is "/" under Unix and Windows

class Path
{
// EXISTS - Test if a file exists
static    function exists($dname)
   {
      return file_exists($dname);
   }

// SIZE - Return the size of a file
static    function size($fname)
   {
      return filesize($fname);
   }

// TYPE - Return the type of an entry: file, dir, etc...
static    function type($fname)
   {
      return filetype($fname);
   }

// DATE - Returns the date of a file
static    function created($fname)
   {
      $t=intVal(filemtime($fname));
      return date("",$t);
   }

// ISFILE
static    function isFile($fname)
   {
      return filetype($fname)==="file";
   }

// ISDIR
static    function isDir($fname)
   {
      $t=filetype($fname);
      if($t==="link")
      {
         return false;
      }
      if($t!="dir")
      {
         return false;
      }
      return true;
   }

// REN
static    function ren($oldname,$newname)
   {
      $b=true;
      rename($oldname,$newname);
      return $b;
   }

// DELETE
static    function erase($fname)
   {
      return unlink($fname);
   }

// MERGE - Merge elements of path
static    function merge($path,$filename)
   {
      if($path==="")
      {
         return $filename;
      }
      if($filename==="")
      {
         return $path;
      }
      $plc=$path{strlen($path)-1};
      $ffc=$filename{0};
      if(($plc!="/")&&($ffc!="/")&&($plc!="\\")&&($ffc!="\\"))
      {
         $path.="/";
      }
      return $path.$filename;
   }

// MAKE DIR - Create a sub-directory
static    function make($name)
   {
      return mkdir($name);
   }

// SPLIT EXT - Split the node and the extension of a filename or path
static    function splitExt($path)
   {
      $l=strlen($path);
      if($l===0)
      {
         return array("","");
      }
      for($x=$l-1;$x>=0;$x+=-1)
      {
         if($path{$x}===".")
         {
            return array(substr($path,0,$x),substr($path,$x+1));
         }
      }
      return array($path,"");
   }

// HAS EXTENSION - Test if the file has an extension or it is inside a list
// the list is an array of extensions separated by a space (with or without dot)
   var $nullarr=array();
static    function hasExtension($path,$extlist=array())
   {
      $pos=strrpos($path,".");
      if($pos===false)
      {
         return false;
      }
      $longext=substr($path,$pos);
      $shortext=substr($longext,1);
      if($shortext==="")
      {
         return false;
      }
      if($extlist===array())
      {
         return true;
      }
      if(in_array($shortext,$extlist))
      {
         return true;
      }
      if(in_array($longext,$extlist))
      {
         return true;
      }
      return false;
   }

// GET EXTENSION - Get extension of a filename or path
static    function getExtension($path)
   {
      $pos=strrpos($path,".");
      if($pos!=false)
      {
         return substr($path,$pos);
      }
      return "";
   }

// CHANGE EXTENSION - Replace current extension by given on
// on filename or full path
static    function changeExt($path,$newext="")
   {
      $l=strlen($path);
      if($l===0)
      {
         return $newext;
      }
      $pos=strrpos($path,".");
      if($pos!=false)
      {
         if($newext{0}===".")
         {
            $path=substr($path,0,$pos);
         }
         else
         {
            $path=substr($path,0,$pos+1);
         }
      }
      return $path.$newext;
   }

// HAS DIR  - Return true if the path has a directory or dir
static    function hasDir($path)
   {
      $l=strlen($path);
      if($l===0)
      {
         return false;
      }
      if($l>1)
      {
         if($path{1}===":")
         {
            return true;
         }
      }
      if(strpos($path,"/")!=false)
      {
         return true;
      }
      if(strpos($path,"\\")!=false)
      {
         return true;
      }
      return false;
   }

// SPLIT  - Split path to directory and file
static    function splitFile($path)
   {
      $l=strlen($path);
      if($l===0)
      {
         return array("","");
      }
      for($x=$l-1;$x>=0;$x+=-1)
      {
         if(($path{$x}==="/")||($path{$x}==="\\"))
         {
            return array(substr($path,0,$x+1),substr($path,$x+1));
         }
      }
      return array("",$path);
   }

// GET DIR Get current directory
   function getDir()
   {
      return getcwd();
   }

// COMPARE PATHS
static    function compare($a,$b)
   {
      $l=strlen($a);
      if($l!=strlen($b))
      {
         return false;
      }
      for($i=0;$i<$l;$i++)
      {
         if(($a{$i}==="\\")||($a{$i}==="/"))
         {
            if($b{$i}==="/")
            {
               continue;
            }
            if($b{$i}==="\\")
            {
               continue;
            }
            return false;
         }
         if($a{$i}!=$b{$i})
         {
            return false;
         }
      }
      return true;
   }

}

?>

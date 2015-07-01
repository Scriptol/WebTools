<?php
include_once("path.php");
include_once("url.php");
include_once("tools.php");
$GRAPHICAL=0;
$LASTMOD=false;
$LASTMODTIME=0;
$PRIORITY=0;
$FREQUENCY=0;
$DEFAULT_FREQUENCY="";
$dateFormat="";
$basePath="";
$version="Simple Map 1.7 (c) 2006-2015  www.scriptol.com";
$logfile=array();
function display($t)
{
   global $GRAPHICAL;
   if($GRAPHICAL===true)
   {
      global $logfile;
      array_push($logfile,$t."\n");
   }
   else
   {
      echo $t, "\n";
   }
   return;
}

// read an ".ini" file, parse lines and return a dict of options
function readIni($inipath)
{
   $ini=array();
   $inipath=Path::changeExt($inipath,"ini");
   if(!file_exists($inipath))
   {
      return;
   }
   $ini=file($inipath);
   if((count($ini)===0))
   {
      display($inipath." file not loaded");
      return;
   }
   foreach($ini as $option)
   {
      $option=trim($option);
      $i=strpos($option,"=");
      if($i===false)
      {
         $i=strpos($option,":");
         if($i===false)
         {
            continue;
         }
      }
      $k=trim(substr($option,0,$i));
      $v=trim(substr($option,$i+1));
      
      if($k==="sitemap")
      {
         global $mapname;
         $mapname=$v;
      }
      else
      {
         if($k==="logfile")
         {
            global $logname;
            $logname=$v;
         }
      else
      {
         if($k==="frequency")
         {
            global $DEFAULT_FREQUENCY;
            $DEFAULT_FREQUENCY=$v;
         }
      else
      {
         if($k==="graphical")
         {
            global $GRAPHICAL;
            $GRAPHICAL=(in_array($v,array("yes","1","true","ok")));
         }
      }}}
   }
   return;
}

function usage()
{
   echo "\n";
   global $version;
   echo $version, "\n";
   echo "--------- Generates an XML, HTML or text site map.", "\n";
   echo "Usage:", "\n";
   echo "    php smap.php [options] url local-path", "\n";
   echo "or  php smap.php [options] remote-path", "\n";
   echo " url:         in the form: www.mysite.com", "\n";
   echo " local-path:  in the form: c:\\mydir\\index.html", "\n";
   echo " remote-path: in the form: www.mysite.com\\index.html", "\n";
   echo "Options:", "\n";
   echo " -v   verbose, display what happens.", "\n";
   echo " -d   debug, output on screen instead.", "\n";
   echo " -m   add a lastmod tag with file date only.", "\n";
   echo " -l   add a lastmod tag in long form, with date and time.", "\n";
   echo " -p   add a priority tag with default value 0.2.", "\n";
   echo " -f   add a change frequency tag with default value in smap.ini.", "\n";
   echo " -t   generate a text sitemap file.", "\n";
   echo " -h   generate a HTML sitemap file.", "\n";
   echo "Output:", "\n";
   echo " sitemap.xml or the name in smap.ini, into the root of the site.", "\n";
   echo "\n";
   exit(1);
   return;
}

function options($num,$args)
{
   $fileindex=0;
   $smapdir="";
   global $GRAPHICAL;
   $GRAPHICAL=false;
   global $VERBOSE;
   $VERBOSE=false;
   global $DEBUG;
   $DEBUG=false;
   global $LASTMOD;
   $LASTMOD=false;
   global $LASTMODTIME;
   $LASTMODTIME=false;
   global $PRIORITY;
   $PRIORITY=false;
   global $FREQUENCY;
   $FREQUENCY=false;

   global $mapType;
   $mapType=0;
   $fileindex=1;

   while($fileindex<$num)
   {
      do
      {
         $option=$args[$fileindex];
         $opt=$option{0};
         if(($opt!='-')&&($opt!='/'))
         {
            break 2;
         }
         $_I1=substr($option,1);
         $_L3=strlen($_I1);
         for($__3 = 0; $__3 < $_L3; $__3++)
         {
            $opt=$_I1{$__3};
            
            $_I2=strtolower($opt);
            if($_I2==="g")
            {
               $GRAPHICAL=true;
            }
            else
            {
               if($_I2==="v")
               {
                  $VERBOSE=true;
               }
            else
            {
               if($_I2==="d")
               {
                  $DEBUG=true;
               }
            else
            {
               if($_I2==="l")
               {
                  $LASTMODTIME=true;
               }
            else
            {
               if($_I2==="m")
               {
                  $LASTMOD=true;
               }
            else
            {
               if($_I2==="p")
               {
                  $PRIORITY=true;
               }
            else
            {
               if($_I2==="f")
               {
                  $FREQUENCY=true;
               }
            else
            {
               if($_I2==="t")
               {
                  $mapType=1;
                  global $mapname;
                  $mapname=Path::changeExt($mapname,".txt");
                  $FREQUENCY=false;
                  $PRIORITY=false;
                  $LASTMOD=false;
                  $LASTMODTIME=false;
               }
            else
            {
               if($_I2==="h")
               {
                  $mapType=2;
                  global $mapname;
                  $mapname=Path::changeExt($mapname,".html");
                  $FREQUENCY=false;
                  $PRIORITY=false;
                  $LASTMOD=false;
                  $LASTMODTIME=false;
               }
            else
            {
               echo $option, " ", "bad option", "\n";
               usage();
            }
            }}}}}}}}
         }
         $fileindex+=1;
      } while(false);
   }
   readIni($args[0]);
   if($LASTMODTIME)
   {
      global $dateFormat;
      $dateFormat="Y-m-d".chr(92)."H:i:s";
   }
   else
   {
      global $dateFormat;
      $dateFormat="Y-m-d";
   }
   if(($num-$fileindex)===2)
   {
      global $WEBADDRESS;
      $WEBADDRESS=$args[$fileindex];
      if(!hasProtocol($WEBADDRESS))
      {
         $WEBADDRESS="http://".$WEBADDRESS;
      }
      $fileindex+=1;
      global $mapname;
      if(!Path::hasDir($mapname))
      {
         $mapname=Path::merge($smapdir,$mapname);
      }
   }
   else
   {
      global $WEBADDRESS;
      $WEBADDRESS="";
   }
   if($DEBUG&&$VERBOSE)
   {
      global $WEBADDRESS;
      echo "Web address in arguments is", " ", $WEBADDRESS, "\n";
   }
   global $logname;
   $_I2=Path::splitFile($args[0]);
   $smapdir=reset($_I2);
   $logname=next($_I2);
   $logname=Path::merge($smapdir,"smap.log");
   global $mapname;
   $mapname="sitemap.xml";

   $fpath=strval($args[$fileindex]);
   if(!Path::exists($fpath))
   {
      display($fpath." not found");
      exit();
   }
   global $basePath;
   global $topname;
   $_I2=Path::splitFile($fpath);
   $basePath=reset($_I2);
   $topname=next($_I2);
   $basePath=noSlash($basePath);

   display("PATHS  base:$basePath top:$topname path:$fpath");
   if(hasProtocol($basePath))
   {
      global $siteURL;
      $siteURL=getURL($basePath);
      if(strlen($siteURL)===strlen($basePath))
      {
         $topname=$fpath;
      }
      global $WEBADDRESS;
      if($WEBADDRESS!="")
      {
         if($WEBADDRESS!=$siteURL)
         {
            display("Error, URLs must match:");
            display("'".$WEBADDRESS."' and '".$siteURL."' differ");
         }
      }
   }
   else
   {
      global $WEBADDRESS;
      if($WEBADDRESS!="")
      {
         global $siteURL;
         $siteURL=$WEBADDRESS;
      }
      else
      {
         display("Error, web adress missing...");
         usage();
      }
   }
   global $siteURL;
   if(!hasProtocol($siteURL))
   {
      $siteURL="http://".$siteURL;
   }
   return;
}

?>

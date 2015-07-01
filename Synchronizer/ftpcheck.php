<?php
// FTP Checker 1.1
// (c) 2007-2010 By Kim Haskell/Denis Sureau
// Requires the PHP interpreter.
// Sources are compiled with the Scriptol to PHP compiler version 7.0.
// www.scriptol.com
//
// The script checks your ftp connection:
// - You can upload a file.
// - You can change date for a file (this may help to update).
//
include_once("path.php");
include_once("dirlist.php");
include_once("ftp.php");
$DISPLAY=false;
$server="";
$source="";
$user="";
$pass="";
$params=array();
$temporary="temporary-file.000.temp";
$connection=0;
function syncConnect()
{
   global $connection;
   global $server;
   $connection=ftp_connect($server);
   if($connection===0)
   {
      die("Not connected");
   }
   global $user;
   global $pass;
   if(ftp_login($connection,$user,$pass)===true)
   {
      echo "Connected on $server as $user", "\n";
      if(ftp_pasv($connection,true)===true)
      {
         echo "Passive mode turned on", "\n";
      }
      else
      {
         echo "Enable to set passive mode", "\n";
      }
      return true;
   }
   else
   {
      echo "Enable to connect as $user on $server", "\n";
   }
   return false;
}

function syncDisconnect()
{
   global $connection;
   ftp_close($connection);
   return;
}

function syncSize($fname)
{
   global $connection;
   return ftp_size($connection,$fname);
}

function syncTime($fname)
{
   global $connection;
   return ftp_mdtm($connection,$fname);
}

function syncDelete($fname)
{
   global $connection;
   $x=ftp_delete($connection,$fname);
   if($x===true)
   {
      echo $fname, " ", "deleted", "\n";
   }
   else
   {
      echo "Enable to delete", " ", $fname, "\n";
   }
   return;
}

function checkUpload($src,$subdir)
{
   $remfile=Path::merge($subdir,$src);
   echo "Uploading $src to $remfile", "\n";
   global $connection;
   if(ftp_put($connection,$remfile,$src,FTP_BINARY)===true)
   {
      echo "$src successfully uploaded", "\n";
   }
   else
   {
      echo "Error, $src not uploaded", "\n";
   }
   return $remfile;
}

function usage()
{
   echo "\n";
   echo "FTP Check 1.0 - (c) 2007-2011 Kim Haskell Scriptol.com", "\n";
   echo "------------------------------------------------------", "\n";
   echo "Syntax:", "\n";
   echo "  php ftpcheck.php [options] source [ftp]", "\n";
   echo "Options:", "\n";
   echo "  -ppassword.", "\n";
   echo "  -llogin.", "\n";
   echo "  -ddirectory", "\n";
   echo "Arguments:", "\n";
   echo "  source: a file to upload", "\n";
   echo "  ftp: remote adr in the form ftp.domain.tld (as ftp.scriptol.com)", "\n";
   echo "If filename is ommitted, the default $temporary file is used", "\n";
   echo "You will be prompted for each other omitted parameter.", "\n";
   exit(0);
   return;
}

function processCommand($argnum,$arguments)
{
   $opt="";
   $remotedir="";
   global $source;
   $source=false;

   if($argnum<2)
   {
      usage();
   }
   reset($arguments);
   do
   {
      $param= current($arguments);
      if(strlen($param)>1)
      {
         $opt=substr($param,0,2);
      }
      else
      {
         usage();
      }
      if($opt==="-p")
      {
         global $pass;
         $pass=substr($param,2);
         if($pass ==false)
         {
            die("-p must be followed by the password.");
         }
         continue;
      }
      if($opt==="-l")
      {
         global $user;
         $user=substr($param,2);
         if($user ==false)
         {
            die("-l must be followed by the login.");
         }
         continue;
      }
      if($opt==="-d")
      {
         $remotedir=substr($param,2);
         if($remotedir ==false)
         {
            die("-d requires a sub-directory.");
         }
         continue;
      }
      if(substr($param,0,4)==="ftp.")
      {
         global $server;
         $server=$param;
         continue;
      }
      if($param{0}==="-")
      {
         echo "Unknown command $param", "\n";
         usage();
      }
      if($source ==false)
      {
         $source=$param;
         continue;
      }
      echo "Unknown command $param", "\n";
      usage();
   }
   while(!(next($arguments) === false));

   global $server;
   if($server ==false)
   {
            echo "FTP location: ";
      $fp=fopen("php://stdin","r");
      $server=rtrim(fgets($fp,65536));
      fclose($fp);
   }
   if($server ==false)
   {
      exit(0);
   }
   if($source ==false)
   {
      global $temporary;
      $source=$temporary;
   }
   if(!file_exists($source))
   {
      die("File $source not found.");
   }
   global $user;
   if($user ==false)
   {
            echo "Login: ";
      $fp=fopen("php://stdin","r");
      $user=rtrim(fgets($fp,65536));
      fclose($fp);
   }
   if($user ==false)
   {
      exit(0);
   }
   global $pass;
   if($pass ==false)
   {
            echo "Password: ";
      $fp=fopen("php://stdin","r");
      $pass=rtrim(fgets($fp,65536));
      fclose($fp);
   }
   if($pass ==false)
   {
      exit(0);
   }
   global $params;
   $params["server"]=$server;
   $params["user"]=$user;
   $params["pass"]=$pass;
   $params["source"]=$source;
   $params["remdir"]=$remotedir;

   return;
}

function main($argc,$argv)
{
   $x=array_slice($argv,1);
   processCommand($argc,$x);
   global $server;
   global $params;
   $server=$params["server"];
   global $user;
   $user=$params["user"];
   global $pass;
   $pass=$params["pass"];
   global $source;
   $source=$params["source"];

   syncConnect();
   $filename=checkUpload($source,$params["remdir"]);
   syncDelete($filename);
   syncDisconnect();
   return 0;
}

main(intVal($argc),$argv);

?>

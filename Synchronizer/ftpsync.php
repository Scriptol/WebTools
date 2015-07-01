<?php
// PHP FTP Synchronizer
// (c) 2007-2015 Scriptol.com. By Kim Haskell & Denis Sureau
// Free under the GNU GPL 2 License.
// Requires the PHP 5 interpreter.
// Sources are compiled with the Scriptol to PHP compiler version 7.0.
// Libraries (c) by Denis Sureau
// www.scriptol.com
//
// The synchronizer updates a website from a local directory.
// - It is able to use techniques to increase the speed.
// - Optionally links in the pages sent are checked.
// Read the manual for details of use.
//

include_once("path.php");
include_once("ftp.php");
include_once("linkcheck.php");
$CHECKMODE=false;
$QUIET=false;
$BACKUP=false;
$ANYFILES=false;
$TOUCHFLAG=true;
$CONTFLAG=false;
$DAYSFLAG=false;
$SKIPPED=false;
$days=0;
$server="";
$user="";
$pass="";
$params=array();
$backdir="";
$temporary="temporary-file.000.tmp";
$connection=0;
$counter=0;
$falsecounter=0;
$problem=0;
function usage()
{
   echo "\n";
   echo "FTP Synchronizer 2.0 - (c) 2007-2015 Scriptol.com", "\n";
   echo "-------------------------------------------------", "\n";
   echo "Syntax:", "\n";
   echo "  solp ftpsync [options] source ftpadr", "\n";
   echo "Options:", "\n";
   echo "  -t test, display only and do nothing.", "\n";
   echo "  -v verbose, display more infos.", "\n";
   echo "  -q quiet, display nothing.", "\n";
   echo "  -a all files, restore the full site", "\n";
   echo "  -c compare contents, ignore time", "\n";
   echo "  -w website url (for the link checker).", "\n";
   echo "  -ndays number of days to upload.", "\n";
   echo "  -ppassword.", "\n";
   echo "  -llogin.", "\n";
   echo "  -fftpadr ftp address in any form.", "\n";
   echo "  -ddirectory remote directory where to upload the files.", "\n";
   echo "  -bbackup, defining a backup directory", "\n";
   echo "Extended options", "\n";
   echo "  -u activate the link checker.", "\n";
   echo "  -k display skipped files.", "\n";
   echo "Arguments:", "\n";
   echo "  source: a directory to backup", "\n";
   echo "  ftpadr: remote adr in the form ftp.domain.tld (as ftp.scriptol.com)", "\n";
   echo "You will be prompted for each omitted but required parameter.", "\n";
   echo "Optionally you will be prompted to add a valid document as RSS item.", "\n";
   echo "See manual for compatibily between options.", "\n";
   exit(0);
   return;
}

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

function filecompare($a,$b)
{
   $x=array();
   $y=array();
   $x=file($a);
   $y=file($b);
   return $x ==$y;
}

function backError($b)
{
   echo "Can't write on backup $b, check device or path and try again...", "\n";
   exit(0);
   return;
}

function checkBackup($bpath)
{
   $tempfile=Path::merge($bpath,"ftpsynxyz.$$$");
   $f=0;
   $error=($f=fopen($tempfile,"w"));
   if($error===false)
   {
      backError($bpath);
   }
   $saved=fwrite($f,"ftp synchro");
   fclose($f);
   if($saved===0)
   {
      backError($bpath);
   }
   else
   {
      global $CONTFLAG;
      if($CONTFLAG)
      {
         echo "Files compared by content", "\n";
         global $TOUCHFLAG;
         $TOUCHFLAG=false;
         return;
      }
      global $TOUCHFLAG;
      $TOUCHFLAG=touch(convertUnix($tempfile),time());
      unlink($tempfile);
      global $QUIET;
      if(!$QUIET)
      {
         if($TOUCHFLAG)
         {
            echo "Files compared by time", "\n";
         }
         else
         {
            echo "Touch failed, files compared by contents", "\n";
         }
      }
   }
   return;
}

function checkRemote($rpath)
{
   global $TOUCHFLAG;
   $TOUCHFLAG=touch($rpath,time());
   return;
}

function filecopy($src,$rmt,$loc)
{
   global $CHECKMODE;
   if($CHECKMODE===true)
   {
      echo "Must upload $src in $rmt", "\n";
      global $falsecounter;
      $falsecounter+=1;
      return;
   }
   global $QUIET;
   if($QUIET===false)
   {
      echo "Uploading $src ";
      global $DAYSFLAG;
      if($DAYSFLAG)
      {
         echo var_export(date('Y-m-d',filemtime($src)),true), "\n";
      }
      else
      {
         echo "to $rmt", "\n";
      }
   }
   global $CHECKLINKS;
   if($CHECKLINKS)
   {
      linkCheckerDiffered($src);
   }
   $putres=0;
   
        try {
    
   global $connection;
   $putres=ftp_put($connection,$rmt,$src,FTP_BINARY);
   
    } catch(Exception $e) { ;  }
    
   if($putres===true)
   {
      global $counter;
      $counter+=1;
      global $BACKUP;
      if($BACKUP===true)
      {
         copy($src,$loc);
         if($loc==="")
         {
            return;
         }
         $b=@touch(@convertUnix($loc),intVal(filemtime($src)));
         global $VERBOSE;
         if($VERBOSE)
         {
            if($b)
            {
               echo "Updated date and time for", " ", convertUnix($loc), "\n";
            }
            else
            {
               echo "Failed to change time for", " ", convertUnix($loc), "\n";
            }
         }
      }
   }
   else
   {
      echo "Error, $src not uploaded", "\n";
   }
   return;
}

function remoteIdentical($lfile,$rfile)
{
   global $DEBUG;
   if($DEBUG===true)
   {
      echo "Comparing $lfile and remote $rfile", "\n";
   }
   global $connection;
   global $temporary;
   if(@ftp_get($connection,$temporary,$rfile,FTP_BINARY)!=true)
   {
      return false;
   }
   $x=array();
   $y=array();
   $x=file($lfile);
   $y=file($temporary);
   return $x ==$y;
}

function backupIdentical($locfile,$bakfile)
{
   $x=array();
   $y=array();
   global $DEBUG;
   if($DEBUG===true)
   {
      echo "Comparing $locfile and local $bakfile", "\n";
   }
   if(!file_exists($bakfile))
   {
      return false;
   }
   if(filesize($locfile)!=filesize($bakfile))
   {
      return false;
   }
   global $TOUCHFLAG;
   if($TOUCHFLAG)
   {
      $a=intVal(filemtime($locfile));
      $b=intVal(filemtime($bakfile));
      if($a===$b)
      {
         return true;
      }
   }
   $x=file($locfile);
   $y=file($bakfile);
   return $x ==$y;
}

function dateCompare($loctime,$numdays)
{
   $numdays+=1;
   $nt=time()-(86400*$numdays);
   return $loctime>=$nt;
}

function synchro($locdir,$bdir,$hostdir)
{
   $content=scandir($locdir);
   $src="";
   $bck="";
   $rmt="";
   $returned=0;
   if($hostdir!="")
   {
      global $VERBOSE;
      global $CHECKMODE;
      if($VERBOSE||$CHECKMODE)
      {
         echo "Creating $hostdir if needed";
         global $BACKUP;
         if($BACKUP)
         {
            echo ", and $bdir";
         }
         echo "\n";
      }
      if(!$CHECKMODE)
      {
                  global $connection;
         @ftp_mkdir($connection,$hostdir);
         global $BACKUP;
         if($BACKUP===true)
         {
            if(!file_exists($bdir))
            {
                              @mkdir($bdir);
            }
         }
      }
   }
   if(empty($content))
   {
      return;
   }
   foreach($content as $name)
   {
      if(substr($src,0,11)==="javascript:")
      {
         continue;
      }
      if($name{0}==="/")
      {
         global $website;
         $src=Path::merge($website,$name);
      }
      else
      {
         $src=Path::merge($locdir,$name);
      }
      global $VERBOSE;
      if($VERBOSE)
      {
         echo "Processing $src", "\n";
      }
      if(filetype($src)==="file")
      {
         $rmt=Path::merge($hostdir,$name);

         global $ANYFILES;
         if($ANYFILES===true)
         {
            filecopy($src,$rmt,"");
            continue;
         }
         if($name{0}===".")
         {
            global $QUIET;
            if(!$QUIET)
            {
               echo $name, " ", "skipped", "\n";
            }
            global $problem;
            $problem+=1;
            continue;
         }
         global $DAYSFLAG;
         if($DAYSFLAG===true)
         {
            global $days;
            if(dateCompare(intVal(filemtime($src)),$days))
            {
               global $BACKUP;
               if($BACKUP===true)
               {
                  $bck=Path::merge($bdir,$name);
                  filecopy($src,$rmt,$bck);
               }
               else
               {
                  filecopy($src,$rmt,"");
               }
            }
            else
            {
               global $SKIPPED;
               if($SKIPPED)
               {
                  echo "  Skipped ", " ", $src, "\n";
               }
            }
            continue;
         }
         global $BACKUP;
         if($BACKUP===true)
         {
            $bck=Path::merge($bdir,$name);
            $returned=backupIdentical($src,$bck);
            if(!$returned)
            {
               filecopy($src,$rmt,$bck);
            }
            else
            {
               global $SKIPPED;
               if($SKIPPED)
               {
                  echo "  Skipped ", " ", $src, "\n";
               }
            }
            continue;
         }
         $returned=remoteIdentical($src,$rmt);
         if(!$returned)
         {
            filecopy($src,$rmt,"");
         }
         else
         {
            global $SKIPPED;
            if($SKIPPED)
            {
               echo "  Skipped ", " ", $src, "\n";
            }
         }
      }
   }
   foreach($content as $name)
   {
      if($name{0}==='.')
      {
         continue;
      }
      $src=Path::merge($locdir,$name);
      if(filetype($src)==="dir")
      {
         synchro($src,Path::merge($bdir,$name),Path::merge($hostdir,$name));
      }
   }
   return;
}

function readLogin()
{
   $loglist=array();
   $loglist=file("ftpsync.login");
   foreach($loglist as $line)
   {
      global $server;
      if(strstr($line,$server))
      {
         $data=explode(" ",$line);
         global $user;
         $user=$data[1];
         global $pass;
         $pass=$data[2];
         return true;
      }
   }
   return false;
}

function processCommand($argnum,$arguments)
{
   $daystring="";
   $opt="";
   if($argnum<2)
   {
      usage();
   }
   foreach($arguments as $param)
   {
      if(strlen($param)>1)
      {
         $opt=substr($param,0,2);
      }
      else
      {
         usage();
      }
      if($opt==="-t")
      {
         global $CHECKMODE;
         $CHECKMODE=true;
         continue;
      }
      if($opt==="-a")
      {
         global $ANYFILES;
         $ANYFILES=true;
         continue;
      }
      if($opt==="-v")
      {
         global $VERBOSE;
         $VERBOSE=true;
         continue;
      }
      if($opt==="-k")
      {
         global $SKIPPED;
         $SKIPPED=true;
         continue;
      }
      if($opt==="-q")
      {
         global $QUIET;
         $QUIET=true;
         continue;
      }
      if($opt==="-~")
      {
         global $DEBUG;
         $DEBUG=true;
         continue;
      }
      if($opt==="-c")
      {
         global $CONTFLAG;
         $CONTFLAG=true;
         continue;
      }
      if($opt==="-u")
      {
         global $CHECKLINKS;
         $CHECKLINKS=true;
         continue;
      }
      if($opt==="-p")
      {
         global $pass;
         $pass=substr($param,2);
         if($pass==="")
         {
            die("-p must be followed by the password.");
         }
         continue;
      }
      if($opt==="-l")
      {
         global $user;
         $user=substr($param,2);
         if($user==="")
         {
            die("-l must be followed by the login.");
         }
         continue;
      }
      if($opt==="-f")
      {
         global $server;
         $server=substr($param,2);
         if($server==="")
         {
            die("-f must be followed by the ftp address.");
         }
         continue;
      }
      if($opt==="-w")
      {
         global $website;
         $website=substr($param,2);
         if($website==="")
         {
            die("-w must be followed by the site url.");
         }
         continue;
      }
      if($opt==="-n")
      {
         $daystring=substr($param,2);
         if($daystring==="")
         {
            die("-n requires a number of days.");
         }
         global $days;
         $days=intval($daystring);
         global $DAYSFLAG;
         $DAYSFLAG=true;
         continue;
      }
      if($opt==="-d")
      {
         global $remotedir;
         $remotedir=substr($param,2);
         if($remotedir==="")
         {
            die("-d requires a sub-directory.");
         }
         continue;
      }
      if($opt==="-b")
      {
         global $backdir;
         $backdir=substr($param,2);
         if($backdir==="")
         {
            die("-b requires a directory.");
         }
         global $BACKUP;
         $BACKUP=true;
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
      global $source;
      if($source==="")
      {
         $source=$param;
         continue;
      }
      echo "Unknown command $param", "\n";
      usage();
   }
   global $BACKUP;
   if($BACKUP===true)
   {
      global $backdir;
      checkBackup($backdir);
   }
   global $server;
   if($server==="")
   {
            echo "FTP location: ";
      $fp=fopen("php://stdin","r");
      $server=rtrim(fgets($fp,65536));
      fclose($fp);
   }
   if($server==="")
   {
      exit(0);
   }
   global $source;
   if($source==="")
   {
            echo "Directory to send: ";
      $fp=fopen("php://stdin","r");
      $source=rtrim(fgets($fp,65536));
      fclose($fp);
   }
   if($source==="")
   {
      exit(0);
   }
   global $user;
   if($user==="")
   {
            echo "Login: ";
      $fp=fopen("php://stdin","r");
      $user=rtrim(fgets($fp,65536));
      fclose($fp);
   }
   if($user==="")
   {
      exit(0);
   }
   global $pass;
   if($pass==="")
   {
            echo "Password: ";
      $fp=fopen("php://stdin","r");
      $pass=rtrim(fgets($fp,65536));
      fclose($fp);
   }
   if($pass==="")
   {
      exit(0);
   }
   global $params;
   $params["server"]=$server;
   $params["user"]=$user;
   $params["pass"]=$pass;
   $params["source"]=$source;
   global $backdir;
   $params["backdir"]=$backdir;
   global $days;
   $params["days"]=$days;
   global $remotedir;
   $params["remdir"]=$remotedir;
   global $website;
   $params["website"]=$website;

   return;
}

function main($argc,$argv)
{
   $x=array_slice($argv,1);
   processCommand($argc,$x);
   global $problem;
   $problem=0;
   global $server;
   global $params;
   $server=$params["server"];
   global $user;
   $user=$params["user"];
   global $pass;
   $pass=$params["pass"];
   global $source;
   $source=$params["source"];
   global $backdir;
   $backdir=$params["backdir"];
   global $days;
   $days=intVal($params["days"]);
   global $website;
   $website=$params["website"];
   global $remotedir;
   $remotedir=$params["remdir"];

   if($website==="")
   {
      $website=preg_replace("/^ftp/i","http://www",$server,1);
   }
   else
   {
      if(!hasProtocol($website))
      {
         $website="http://".$website;
      }
   }
   global $QUIET;
   if(!$QUIET)
   {
      global $VERBOSE;
      if($VERBOSE===true)
      {
         echo "Verbose mode enabled", "\n";
      }
      global $DEBUG;
      if($DEBUG===true)
      {
         echo "Debug mode enabled", "\n";
      }
      global $DAYSFLAG;
      if($DAYSFLAG)
      {
         echo "Update files changed ";
         if($days>0)
         {
            echo "within", " ", var_export($days+1,true), " ", "days", "\n";
         }
         else
         {
            echo "last day", "\n";
         }
      }
      echo "Source directory: $source", "\n";
      echo "Remote directory:", " ", $remotedir, "\n";
      global $BACKUP;
      if($BACKUP===true)
      {
         echo "Backup location: $backdir", "\n";
      }
      global $ANYFILES;
      if($ANYFILES===true)
      {
         echo "Website will be restored", "\n";
      }
      global $CHECKLINKS;
      if($CHECKLINKS)
      {
         echo "Link checker active", "\n";
      }
   }
   syncConnect();
   echo "Synchronizing $source on $server", "\n";
   synchro($source,$backdir,$remotedir);
   syncDisconnect();
   if($QUIET)
   {
      return 0;
   }
   global $counter;
   echo $counter," file",($counter>1?"s":"")," copied";
   global $CHECKMODE;
   if($CHECKMODE)
   {
      global $falsecounter;
      if($falsecounter>0)
      {
         echo ", ",$falsecounter," file",($falsecounter>1?"s":"")," to update";
      }
      else
      {
         echo ", nothing to update";
      }
   }
   echo ".", "\n";
   if($problem>0)
   {
      echo "$problem file".($problem>1?"s":""), " ", "skipped.", "\n";
   }
   global $CHECKLINKS;
   if($CHECKLINKS)
   {
      differedCheck();
      if($counter>0)
      {
         dispBroken();
      }
   }
   return 0;
}

main(intVal($argc),$argv);

?>

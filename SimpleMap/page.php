<?php

include_once("path.php");

class PageWeb
{
   var $page="";
   var $headstart=0;
   var $headend=0;
   var $heading="";
   var $filename="";
   function load($path)
   {
      if(!Path::exists($path))
      {
         return;
      }
      if(!Path::isFile($path))
      {
         return;
      }
      $f=fopen($path,"r");
      $fs=intVal(filesize($path));
      $this->page=fread($f,$fs);
      fclose($f);
      $this->filename=$path;

      return;
   }

   function getMetaValue($tagname)
   {
      $name="";
      $value="";
      $metastart=0;
      $starting=0;
      $ending=0;
      $tagname=strtolower($tagname);

      $start=stripos($this->heading,"meta");
      if($start===false)
      {
         return "";
      }
      while($start<strlen($this->heading))
      {
         do
         {
            $metastart=stripos($this->heading,"name",$start);
            if($metastart===false)
            {
               return "";
            }
            $starting=strpos($this->heading,"\"",$metastart);
            if($starting===false)
            {
               $starting=strpos($this->heading,"\'",$metastart);
            }
            if($starting===false)
            {
               return "";
            }
            $starting+=1;
            $ending=strpos($this->heading,"\"",$starting);
            if($ending===false)
            {
               $ending=strpos($this->heading,"\'",$starting);
            }
            if($ending===false)
            {
               return "";
            }
            $name=substr($this->heading,$starting,$ending-($starting)+strlen($this->heading)*(($ending<0)-($starting<0)));
            $name=trim($name);

            if($name!=$tagname)
            {
               break;
            }
            $starting=stripos($this->heading,"content",$ending+1);
            if($starting===false)
            {
               return "";
            }
            $starting+=7;
            $starting=stripos($this->heading,"=",$starting);
            if($starting===false)
            {
               return "";
            }
            $metastart=$starting;

            $starting=strpos($this->heading,"\"",$metastart);
            if($starting===false)
            {
               $starting=strpos($this->heading,"\'",$metastart);
            }
            if($starting===false)
            {
               return "";
            }
            $starting+=1;

            $ending=strpos($this->heading,"\"",$starting);
            if($ending===false)
            {
               $ending=strpos($this->heading,"\'",$starting);
            }
            if($ending===false)
            {
               return "";
            }
            $value=substr($this->heading,$starting,$ending-($starting)+strlen($this->heading)*(($ending<0)-($starting<0)));
            $value=trim($value);

            break 2;
         } while(false);
         $start+=4;
      }
      return $value;
   }

   function getHead()
   {
      $state=true;
      $this->heading="";

      $starting=stripos($this->page,"<head");
      if($starting===false)
      {
         return;
      }
      $starting=strpos($this->page,">",$starting);
      if($starting===false)
      {
         return;
      }
      $ending=stripos($this->page,"</head");
      if($ending===false)
      {
         return;
      }
      $this->headstart=$starting;
      $this->headend=$ending;

      $this->heading=substr($this->page,$this->headstart+1,$this->headend-($this->headstart+1)+strlen($this->page)*(($this->headend<0)-($this->headstart+1<0)));

      return;
   }

   function indexable()
   {
      $this->getHead();
      if($this->heading==="")
      {
         return true;
      }
      $rob=$this->getMetaValue("robots");
      if($rob==="")
      {
         return true;
      }
      if(stripos($rob,"noindex")!=-1)
      {
         return false;
      }
      if(stripos($rob,"none")!=-1)
      {
         return false;
      }
      return true;
   }

}
function checkIndexable($fname,$skipped)
{
   $pw=new PageWeb();
   $localname="";
   $flag=0;
   $_I1=localPath($fname);
   $localname=reset($_I1);
   $flag=next($_I1);

   if(in_array($localname,$skipped))
   {
      return false;
   }
   $pw->load($localname);
   if(!$pw->indexable())
   {
      echo $localname, " ", "skipped, NOINDEX", "\n";
      array_push($skipped,$localname);
      return false;
   }
   return true;
}

?>

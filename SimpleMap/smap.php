<?php
//	  Simple Map - XML, text or HTML Site Map  Generator
//	  A free open-source Scriptol program,  by (c) 2006-2009 Denis G. Sureau
//	  http://www.scriptol.com/
//	  Build a Google compatible  site map,  or a Html map.
//	  Requirement:
//	  - May be compiled with the Scriptol Compiler version 6.0
//	  Licence: Mozilla 1.1
//	  - absolutely free to use and distribute
//	  - keep this source open if you modify it
//	  - don't change the copyright above
include_once("path.php");
include_once("libphp.php");
include_once("options.php");
include_once("parser.php");
// Main function
function main($num,$args)
{
   if($num<2)
   {
      usage();
   }
   SimpleMap($num,$args);
   return 0;
}

// Call the main function
main(intVal($argc),$argv);

?>

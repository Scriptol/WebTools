
include "path.sol"
include "url.sol"
include "tools.sol"

boolean GRAPHICAL
boolean LASTMOD = false
boolean LASTMODTIME
boolean PRIORITY
boolean FREQUENCY
text DEFAULT_FREQUENCY
text dateFormat
text basePath

enum  OUT_XML, OUT_TEXT, OUT_HTML 


text version = "Simple Map 1.7 (c) 2006-2015  www.scriptol.com"


array logfile = []


void display(text t)
	if GRAPHICAL = true
		logfile.push(t + "\n")
	else	
		print t		
	/if	
return

# read an ".ini" file, parse lines and return a dict of options

void readIni(text inipath)

  array ini
  inipath = Path.changeExt(inipath, "ini")
  if not file_exists(inipath) return
  ini.load(inipath)
  if(ini.size() = 0)
    display(inipath + " file not loaded")
    return
  /if

  for text option in ini
    option = option.trim()        // remove trailing spaces and eol
    int i = option.find("=")      // parse a line
    if i = nil  
		i = option.find(":")
		if i = nil	continue          // no valid couple key=value
	/if	
    text k = option[--i].trim()   // key
    text v = option[i+1..].trim() // value
    if k
	= "sitemap":		mapname = v
    = "logfile":		logname = v
	= "frequency":	DEFAULT_FREQUENCY = v
	= "graphical":	GRAPHICAL = (v in [ "yes", "1", "true", "ok" ])
    /if
  /for

return



void usage()
	print
	print version
	print "--------- Generates an XML, HTML or text site map."
    print "Usage:"
    print "    php smap.php [options] url local-path" 
    print "or  php smap.php [options] remote-path"
    print " url:         in the form: www.mysite.com"
    print " local-path:  in the form: c:\\mydir\\index.html"
    print " remote-path: in the form: www.mysite.com\\index.html"
    print "Options:"
    print " -v   verbose, display what happens."
    print " -d   debug, output on screen instead."
    print " -m   add a lastmod tag with file date only."
    print " -l   add a lastmod tag in long form, with date and time."
    print " -p   add a priority tag with default value 0.2."
    print " -f   add a change frequency tag with default value in smap.ini."
    print " -t   generate a text sitemap file."
    print " -h   generate a HTML sitemap file."	
    print "Output:"
    print " sitemap.xml or the name in smap.ini, into the root of the site."
    print
	
	exit(1)
	
return

void options(int num, array args)

	int fileindex
	text smapdir

	GRAPHICAL = false
	VERBOSE = false
	DEBUG = false
	LASTMOD = false
	LASTMODTIME = false
	PRIORITY = false
	FREQUENCY = false
	
	mapType = OUT_XML
	fileindex = 1
	

	while fileindex < num 
		text option = args[fileindex]
		text opt = option[0]
		
		if (opt <> '-') and (opt <> '/') break
		
		for opt in option[ 1 .. ]
			if opt.lower()
			= "g" : GRAPHICAL = true
			= "v" : VERBOSE = true 
			= "d" : DEBUG = true
			= "l" : LASTMODTIME = true	// last modif date and time 
			= "m" : LASTMOD = true	// last modif date only
			= "p" : PRIORITY = true
			= "f" : FREQUENCY = true
			= "t": 	mapType = OUT_TEXT		// output a text file (default xml)
						mapname = Path.changeExt(mapname, ".txt")
						FREQUENCY = false
						PRIORITY = false
						LASTMOD = false
						LASTMODTIME = false
			= "h": 	mapType = OUT_HTML		// output a text file (default xml)
						mapname = Path.changeExt(mapname, ".html")
						FREQUENCY = false
						PRIORITY = false
						LASTMOD = false
						LASTMODTIME = false
			else 
				print option, "bad option"
				usage()
			/if
		/for
		fileindex + 1
	/while
	
	readIni(args[0])
	
    if LASTMODTIME
       dateFormat =  "Y-m-d" + chr(92) + "H:i:s"   // code 92 for antislash T
    else
       dateFormat = "Y-m-d" 
    /if       


	// two parameters remaining?  url + local path
	if (num - fileindex) = 2
		WEBADDRESS = args[fileindex]
		if not hasProtocol(WEBADDRESS)
			WEBADDRESS = "http://" + WEBADDRESS
		/if	
		fileindex + 1
		if not Path.hasDir(mapname)
				mapname = Path.merge(smapdir, mapname)
		/if	
	else
		//// remote url
		WEBADDRESS = ""
	/if	
	
	if DEBUG and VERBOSE
		print "Web address in arguments is", WEBADDRESS
	/if	
	
	smapdir, logname = Path.splitFile(args[0])
	logname = Path.merge(smapdir, "smap.log")
	mapname = "sitemap.xml"
	
	text fpath = args[fileindex].toText()
	if not Path.exists(fpath)
		display(fpath + " not found")
		exit()
	/if

	basePath, topname = Path.splitFile(fpath)
	basePath = noSlash(basePath)
	
	display("PATHS  base:$basePath top:$topname path:$fpath")
	
	if hasProtocol(basePath)
		siteURL = getURL(basePath)
		if siteURL.length() = basePath.length() ? topname = fpath
		if WEBADDRESS <> ""
			if WEBADDRESS <> siteURL
				display("Error, URLs must match:")
				display("'" +WEBADDRESS + "' and '" + siteURL + "' differ")
			/if
		/if	
	else
		if WEBADDRESS <> ""
			siteURL = WEBADDRESS
		else
			display("Error, web adress missing...")
			usage()
		/if	
	/if
	
	if not hasProtocol(siteURL)
		siteURL = "http://" + siteURL
	/if	
	

return


#	Simple Map - XML, text or HTML Site Map  Generator
#	A free open source Scriptol program,  by (c) Denis G. Sureau
#	http://www.scriptol.com/
     

include "path.sol"
include "libphp.sol"
include "tools.sol"
include "options.sol"
include "url.sol"
include "page.sol"

array siteList = []  	// source of sitemap, list of pages in full URL with site address + path + filename
dict siteTime = []		// dict of last mod date+time for each URL 
array checkedList = []	// list of check links
array skipped			// page skipped once must be ignored again
dict defaultList = []   // dict of internal directories and their home pages

text pageText
array siteMap = []


void output(text t)
	siteMap.push(t)
return


// get the part of tag inside quotes or double-quotes, thus the url

text extractLink(int off)
	text c
	text link = ""
	while off < pageText.length()
		c = pageText[off]
		if c = '='  continue
		if c = ' '  continue
		if c = "\""  : off + 1; break; /if
		if c = '\'' : off + 1; break; /if
		break
	let off + 1
	while off < pageText.length()
		c = pageText[off]
		if c = '\'' break
		if c = "\""  break
		if c = '>'  break
		link + c             // anything else is part of the url
	let off + 1
return link.trim()


// Test if page already scanned

boolean inCheckedList(text page)
	if not hasProtocol(page)
		page = createLinkFromRelative(page)
	/if
	if page in checkedList return true
return false	


// Test if page already added to list

boolean inSiteMap(text page)
	if not hasProtocol(page)
		page = createLinkFromRelative(page)
	/if
	if page in siteList return true
return false	


// Add page to list, even if not valid, to not scan it again

void addFileList(text page)
	if not hasProtocol(page)
		page = createLinkFromRelative(page)
	/if
	if page in checkedList return 
	checkedList.push(page)
return 


// Add to the site map list of all pages to be referenced


boolean addLink(text page, real lmod)

	//if not validExtension(fname) return false
	// add site url if required
	text url
	
	url = setURL(page) 	// convert to slashes
	
	if not hasProtocol(page)
		url = Path.merge(siteURL, url)
	/if	
	
	if url in siteList return false
	
	siteList.push(url)

    if LASTMOD	
	   siteTime[url] = date(dateFormat, lmod)
	/if   
	
	display("Added link $url")
return true	




boolean isIndexable(text fullpath)
    dict tags = get_meta_tags(fullpath)
    text robots = tags['robots']
    if robots = nil return true
	if stripos(robots, "noindex") <> false return false
	if stripos(robots, "none") <> false return false   
return true


// Get the URL from the text, skipping other chars
// Return a list of files with a subdirectory or not, and of URLs

array getLinks(text page)
	array x = []
	
	x.load(page)

	if DEBUG print "Getting links from ", getcwd(), page

	array pageLinks = []		// local links found on this page
  
	// removing ending special codes and building a text from the array
	scan x let x[] = x[].toText().rtrim()
	pageText = x.join("")           // merging lines into a text

	int offset = 0
	int srcoff = 0
	int shifting = 0
	
	while forever
		if offset <> -1 let offset = pageText.find("href", shifting)
		if srcoff <> -1 
			srcoff = pageText.find("frame src", shifting)
			if srcoff = -1
				srcoff = pageText.find("frame src", shifting)
			/if	
		/if	

		if offset < 1
			if srcoff < 1	break
			shifting = srcoff + 5
		else
			if srcoff < 1
				shifting = offset
			else
				if offset < srcoff
					shifting = offset
				else
					shifting = srcoff + 5
				/if
			/if
		/if	
		
		shifting + 4
		
		text link = extractLink(shifting)
		
		//if (shifting - 4) = srcoff print ">", fname, "---", link		
		shifting + link.length()
	
		if link = "" 			continue
		if link[0] = '#' 		continue	// anchors are omitted
		if link in siteList 	continue	// page already scanned, don't add it to list
		if link in pageLinks 	continue	// page not scanned, but link already added
		
		if not validExtension(link) and not isDirectory(link) continue
		
		if "../" in link
			display(" ../ such path is not valid, use absolute URL instead (in " + link+").")
			continue
		/if
		
		if hasProtocol(link)
			text host = getURL(link)
			//print "LINK WITH PROTOCOL", link, host, "=", (host = siteURL)
			if host = siteURL	// this is the same current host, add the link
				pageLinks.push(link)
			/if
			continue	// same host or not, go to next line
		/if
    
		// local link, without web address, 
        // may be a simple html file or a subdirectory with a filename
		
		//print "ADDING IN GETLINK (" , getcwd(), ") ($link) "
        
        text realLink = link
        if isDirectory(link) 
            link = findDefault(link)
            //print "GETLINK dir $link"
        /if                  
 		if WINDOWS let link = setWindows(link)		

		if file_exists(getcwd() + "/" + link)
			pageLinks.push(realLink)					
		else
			if DEBUG or VERBOSE	print "Broken link: $link not in", getcwd()
		/if
	
  /while
  
  //pageLinks.display()
  
return pageLinks


// Parse a web page 
// The argument may be:
// - a full URL (from any directory)
// - a filename  (from the local directory of the file)
// - a subdirectory

// Verify it is valid otherwise return
// If the filename is an URL
// - translate it into local path
// - keep the url for the map
// Otherwise it is a filename in the directory
// - translate path into url for the map
// In all case
// - Extract internal links, either url or relative paths
// - And call the function again for each of them

int scanPage(text page)
	int counter = 0		// number of link in this page
	text fullpath		// full local path of this page
	real lmod			// time of last modification of this page
	text link
	text linkBase		// sub-dir for the link to create
	text localDir
	text pageDir        // current dir
	text temp
	text realPage

	addFileList(page)
 	if not parsable(page) return 0

	if hasProtocol(page)
	    //print "SCANPAGE HAS PROTOCOL $page"
	    link = page
		if not isInternal(page)	return 0		
		page = getLocal(page)       // remove local root dir
		localDir, page = Path.splitFile(page)  // separate dir and filename
		localDir = Path.merge(localRoot, localDir)
		fullpath = Path.merge(localDir, page)
	else
        fullpath = Path.merge(getcwd(), page)		
		link = createLinkFromName(page)
		//print "NP CREATE full $fullpath, name=", page, "link=", link
	/if

    if WINDOWS let fullpath = setWindows(fullpath)
	if isDirectory(fullpath)
	   fullpath = findDefault(fullpath)
	   addFileList(fullpath)
	/if

	//print "Now page is $fullpath"
	
	if not file_exists(fullpath)
		display("   " + fullpath + " not found")
		return 0
	/if

 	if not isIndexable(fullpath)
        display("    $fullpath is NOINDEX, skipped")
        return 0
 	/if

    
    // localdir is the current directory once moved to the local dir of the page
    // must be fullpath minus the filename
	changeDir(localDir)

	if DEBUG or VERBOSE
		print "Scanning page: $fullpath"
	/if	

   	lmod = filetime(fullpath)	
	if not addLink(link, lmod) return counter	// already scanned

	
	text k, v
	counter + 1

	array dep = getLinks(fullpath)			// get local links in this page
	if dep = nil return counter
	
	//dep.display()
	
	if VERBOSE or DEBUG
		print dep.size(), "internal links found"
	/if

	// Now processing each link if it is a local page and call this function for it
		
	for text name in dep
		if not isInternal(name)
            if DEBUG print "$name external, skipped." 
            continue
        /if    
		
		if inSiteMap(name) = true continue

		if isDirectory(link)
            temp = findDefault(name)
            defaultList[temp] = name
            name = temp
        /if    
		
		if hasProtocol(name)
			pageDir = getcwd()        // save current directory
			counter + scanPage(name)			// scan this URL
			changeDir(pageDir)
			continue
		/if
	
		if Path.hasDir(name)
            // remove sub-directory and change to it
            text ndir, filename 
			ndir, filename = Path.splitFile(name)
			pageDir = getcwd()        // save current directory
			changeDir(ndir)
			counter + scanPage(filename)			// scan this page
			changeDir(pageDir)
		else
            counter + scanPage(name)			// scan each link in this page
		/if	
	/for

return counter



void buildTag(text tagname, text value)
	if value = "" return
	output("     <" + tagname + ">" + value + "</" + tagname + ">")
return



# From the list of files build now a xml file

void buildTheXmlFile()

	text name

	output("<?xml version=\"1.0\" encoding=\"UTF-8\"?>") 
	output("<urlset xmlns=\"http://www.google.com/schemas/sitemap/0.84\" ")
	output("xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" ")
	output("xsi:schemaLocation=\"http://www.google.com/schemas/sitemap/0.84 ")
	output("http://www.google.com/schemas/sitemap/0.84/sitemap.xsd\">")
	
	for name in siteList
	
		output("  <url>")
		
		buildTag("loc", textToUTF8(name))	// create a tag, content UTF-8 compatible

		if PRIORITY = true
			buildTag("priority", text(0.2))
		/if
	
		if (LASTMOD = true) or (LASTMODTIME = true)
			text lmod = siteTime[name]
			if lmod <> nil	let buildTag("lastmod", lmod)
		/if
		
		if FREQUENCY = true
			buildTag("changefreq", DEFAULT_FREQUENCY)
		/if	
		
		output("  </url>")
	/for	

	output("</urlset>")
	
return


int levelOffset



# create a tag, content UTF-8 compatible

void buildLink(text link)

	text linkpath, name	

	output("<li>")
	link = textToUTF8(link)
	linkpath, name = Path.splitFile(link)
	output("<a href=" + link + ">" + name + "</a>")	
	output("</li>")
	
return

# extract dir

void openSubdir(text dirname)
	output("<br>")
	output("<h4>")
	output(dirname)
	output("</h4>")
	output("<ul>")
return

void closeSubdir()
	output("</ul>")
return

text removeBase(text linkpath, text base)
	int l = base.length()
	
	//print "enter remove to ", linkpath[0 -- l] , "and",  linkpath[l + 1 .. ]
	
	if l <  linkpath.length()
		if linkpath[0 -- l] = base return linkpath[l + 1 .. ]
	/if	

return linkpath

# test if the local part of the URL holds a directory

boolean hasDir(text name)
	int l = name.length()
	if l < 2 return false
	if name.find("/", 1) <> nil return true
return false	

# get dir

text getMainDir(text name)
	if name.length() = 0 return ""
	int i = name.find("/")
	if i < 1 return ""
return name[ -- i]
	


// parse the list of link at offset
// extract files
// and get position of first subdir
// base is the url plus the current path but filename

void processDirs(text base)

	text page
	text name, linkpath
	boolean empty = true
	text currdir = ""
	text thisdir
	
	// get all the files, display them
	
	for int i in 0 -- siteList.size()	
		if siteList[i] = "" continue
		page = siteList[i]
		name = removeBase(page, base)
		
		if not Path.hasDir(name)
			buildLink(page)
			siteList[i] = ""
		else
			empty = false
		/if	
	/for	

	if empty = true return

	// now we have only paths with dirs inside
	// for each dir dir in the list we have to call this function
	// this identify this dir and all other dirs

	for int i in 0 -- siteList.size()	
		if siteList[i] = "" continue
		page = siteList[i]
		name = removeBase(page, base)
	
		thisdir = getMainDir(name)
		if thisdir <> currdir
			openSubdir(thisdir)
			processDirs(base + "/" + thisdir)
			closeSubdir()	
			currdir = thisdir
		/if	
	/for

return


# Scan list of links
# build a text array of page in sub-dirs
# make an entry for file in current directory

void processRoot()

	text urlpart	
	text name
	text link
	
	// process files

	output("<br>")
	output("<h1>")
	output(siteURL)
	output("</h1>")

	scan siteList
		link = siteList[]
		urlpart, name = splitURL(link)
		if Path.hasDir(name) <> true
			buildLink(link)
			siteList[] = ""
		/if	
	/scan	

	processDirs(siteURL)

return

# from the list of files
# build now a html page
# process file at the level
# then get first sub-dir, and loop

void buildTheHtmlTree()
	text name

	output("<html>")
	output("<head>")
	output("</head>")
	output("<body>")

	levelOffset = siteURL.length()
	processRoot()

	output("</body>")
	output("</html>")
	
return



# Main Simple Map function

int SimpleMap(int num, array args)
	
	boolean changed = false
	int counter = 0
	
	options(num, args)
	print
	print version
	
	text currentPath = getcwd()
	if currentPath <> basePath
		if VERBOSE = true print "Leaving", currentPath
		changeDir(basePath)	
		changed = true
	/if	
 
    text realTop = topname
    if isDirectory(topname)
        topname = findDefault(topname)
    /if

    if not validExtension(topname)
        print "Filename missing or not a valid extension in local path." 
        return 0 
    /if    

	if VERBOSE
		print "Web adress:", siteURL
	/if	
	
	localRoot = getcwd()
	
	for text t in localRoot
		if t = "\\" 
			WINDOWS = true
			break
		/if
	/for		

	basePath = ""   	// we are in the path
	counter  + scanPage(topname)
	
	display("")
	siteList.sort()
	
	if VERBOSE = true print "Creating", mapname

	if mapType
	= OUT_XML:		buildTheXmlFile()
	= OUT_TEXT:		siteList.store(mapname) 		// a text file
	= OUT_HTML:		buildTheHtmlTree()
	/if
	
	if mapType <> OUT_TEXT
		file x = fopen(mapname, "w")
		for text t in siteMap
			x.write(t + "\n")
		/for
		x.close()	
	/if

	logfile.store(logname)	
	display( text(siteList.size()) + " pages added to " + mapname + ", " + text(checkedList.size()) + " checked.")

	if changed = true
		if VERBOSE = true print "now returning to original path", currentPath
		changeDir(currentPath)
	/if	

return 0


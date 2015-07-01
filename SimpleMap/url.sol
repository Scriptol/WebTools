include "path.sol"


boolean WINDOWS = false
boolean DEBUG = false
boolean VERBOSE = false

text siteURL = ""

int mapType
text mapname
text WEBADDRESS
text logname          // path and name of the log file
text topname = ""
text localRoot

array extensions = ["htm", "html", "php", "php3", "php4", "php5", "asp",
    "shtml", "dhtml", "jsp", "xhtml", "sol", "txt"]

array pagext = ["htm", "html", "php", "php3", "php4", "php5", "asp",
    "shtml", "dhtml", "jsp", "xhtml"]


// test if this is a remote  address (host included in the string)

bool hasProtocol(text theurl)
	text lowname = theurl.ltrim().lower()
	if lowname[ .. 6] = "http://"	return true
	if lowname[ .. 5] = "ftp://" 	return true
	if lowname[ .. 7] = "https://"	return true
return false

// Position in the URL minus protocol

int siteOffset(text theurl)
	text lowname = theurl.ltrim().lower()
	if lowname[ .. 6] = "http://"	return 7
	if lowname[ .. 5] = "ftp://" 	return 6
	if lowname[ .. 7] = "https://"	return 8
return 0


// return remote part and local part

text, text splitURL(text theurl)
	int offset = siteOffset(theurl)
	if offset = nil ? return "", theurl
	offset = theurl.find("/", offset)
	if offset = nil return theurl, ""
return theurl[-- offset], theurl[offset +1 ..]


// get the remote part of URL

text getURL(text theurl)
	int offset = siteOffset(theurl)
	offset = theurl.find("/", offset)
	if offset = -1 return theurl      // no file or subdir
return theurl[--offset]


// URL of a directory

text findDefault(text thedir)
    text url = null

    for text ext in pagext
        url = thedir + "index." + ext
        if file_exists(url) return url
    /for

    for text ext in pagext
        url = thedir + "default." + ext
        if file_exists(url) return url
    /for

    for text ext in pagext
        url = thedir + "home." + ext
        if file_exists(url) return url
    /for

    for text ext in pagext
        url = thedir + "accueil." + ext
        if file_exists(url) return url
    /for
        
    url = thedir + "index"
    if file_exists(url) return url
    url = thedir + "home"
    if file_exists(url) return url
    url = thedir +  "accueil"
	if file_exists(url) return url
	
return thedir


# Retrieve the local path of the file from a full URL
# Remove the URL of the site (http://www.scriptol.com)
# For example: 
# url is                 http://www.scriptol.com/ajax/index.php 
# local dir is           c:\scriptol\
# the function returns   c:\scriptol\ajax\index.php
# returns also true as second value if it is interal to the site
# and false if it is a link to another website

text, boolean localPath(text name)

    int p = siteOffset(name)
    if p = 0 return name, true
    
    name = name[p ..]       // protocol removed
	
	int l = siteURL.length()
	if siteURL[ -- l] = "/" let l - 1		 // remove trailing slash from offset count
	text lowname = name.lower()
	if lowname[ -- l] = siteURL				 // internal link
		if lowname[l] = "/" let l + 1		
		if lowname.length() > l
			name = Path.merge(localRoot, name[l .. ]) // add local base to remote path 
			if WINDOWS let name = setWindows(name)
		else
			name = ""
		/if
        return name, true	// relative part
	/if	
	if DEBUG
		print name, "not in this website, ignored."
	/if	
		
return "", false


// Check if the URL is internal to the site

boolean isInternal(text name)
    int p = siteOffset(name)
    if p = 0 return true       // not protocol, always internal
    
    if siteURL.lower() in name.lower() return true
	if DEBUG print name, "not in this website, ignored."
return false


boolean validExtension(text fname)
	text node
	text ext
	node, ext = Path.splitExt(fname)
	
    // only html or textual pages are parsed for links
	if ext.lower()  in extensions return true
return false



boolean parsable(text fname)
	text node
	text ext
	node, ext = Path.splitExt(fname)
	
 // only html pages are parsed for links
    if isDirectory(fname) return true
	if ext.lower()  in pagext return true
return false


// get the local part 

text getLocal(text theurl)
    int slash = 1
	int offset = siteOffset(theurl)
	if offset = 0  return theurl       // already local
	
	int len = siteURL.length()
    
    if not hasProtocol(siteURL) let len + offset
	
	text suffix = siteURL[-1 ..]
	if (suffix = "/") or (suffix = "\\") let slash = 0
return theurl[len + slash ..]



// create a link from the local path and the filename and the site's URL

text createLinkFromName(text name)
    text local = getcwd()
    // removing local root dir
	int l =  localRoot.length()
	if local.length() > l
		if localRoot.lower() = local[ -- l].lower()
			local = local[ l + 1 .. ]		// remove local root and skip slash
		/if
	else
        local = ""	
	/if

    // adding remote base url
    text p = siteURL + "/" + local
	text suffix = p[ -1 ..]
	
	if (suffix = "/") or (suffix = "\\")
	   return p + name
	/if
return p + "/" + name


// create a link from a relative path and filename 

text createLinkFromRelative(text name)

	text p = siteURL + "/" + getcwd()   
	text suffix = p[ -1 ..]
	
	if (suffix = "/") or (suffix = "\\")
	   return p + name
	/if
	
return p + "/" + name

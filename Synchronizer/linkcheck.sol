#
# LinkCheck - Scriptol Library
# http://www.scriptol.com/compiler/
# Licence: LPGL
# Check an HTML page for broken links
#
# (c) 2008-2016 by Denis Sureau. Scriptol.com
#

include "dom.sol"
include "path.sol"

boolean CHECKLINKS = false
boolean VERBOSE = false   // True to display more details
bool QUIET = false     // True to display nothing
boolean DEBUG = false     // even more verbose

text website = ""   // the website base URL (protocol, domain, tld)
text source = ""	// local directory at start
text remotedir = "" // sub-directory on the remote server but not in the public URL
int rdlength = 0

array broken = ["Links report:"]   // list of pages and broken links
array pagesToCheck = []
int brocount = 0

array extensions = [".html", ".php", ".htm", ".php5", ".asp",
    ".shtml", ".dhtml", ".jsp", ".xhtml", ".stm"]


// Obtain the HTTP status code for a given web page
// 200=OK  301=redirect 302= temp redirect ignored 404=missing
// All the codes are on http://www.scriptol.org/dictionary/http-code.php

// For http

int httpAccess(text url)
  text errno
  text errstr
  text page
  text site
  var fp

  if url.length() < 8 return 0  
  if url[ .. 6].lower() <> "http://" return 0
    
  int l = strpos(url, "/", 8)
  if l < 1
    site = url[7 ..]
    page = "/"
  else  
    site = url[7 -- l]
    page = url[ l .. ]
  /if  
  
  fp = @fsockopen(site, 80, errno, errstr, 30);
  if fp = false 
     print "Error $errstr ($errno) for $url viewed as site:$site page:$page"
     return 0
  /if  
  
  text out = "GET /$page HTTP/1.1\r\n"
  out  + "Host: $site\r\n"
  out  + "Connection: Close\r\n\r\n"

  fwrite(fp, out)
  text content = fgets(fp)
  text code = trim(substr(content, 9, 4))
  fclose(fp)
  
  int icode = intval(code) 
  if icode = 404
    file f = @fopen(url, "r")
    if f <> nil
        text cnt = @fread(f, 128)
        if strlen(trim(cnt)) > 0 let icode = 200
        fclose(f)
    /if    
  /if  

return icode

// For https

int httpsAccess(text url)
    if url.length() < 9 return 0  
    var headers
    var code
    ~~
    if(function_exists("curl_init")) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($c, CURLOPT_VERBOSE, false);
        curl_setopt($c, CURLOPT_URL, $url);        
        curl_setopt($c, CURLOPT_HEADER, true);
        curl_setopt($c, CURLOPT_NOBODY, true);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        $headers = curl_exec($c);
        $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);
    }
    else {
        return(0);
    }
    ~~    
return code


// change formats

text convertUnix(text src)
return str_replace( "\\", "/", src)


# remove trailing slash or backslash

text noSlash(text pth)
	text c = pth[ -1 ..]
	if (c = "/") or (c = "\\") return pth[ .. -2]
return pth



int siteOffset(text theurl)
   int offset = 0
   offset = theurl.find("http://")
   if offset = nil
		offset = theurl.find("ftp://")
		if offset = nil
			offset = theurl.find("https://")
			if offset <> nil
				offset + 8
			/if
		else
			offset + 6
		/if  
   else
		offset + 7
   /if
return offset



# test if this is a remote  address (host included in the string)

boolean hasProtocol(text theurl)
	text lowname = theurl.ltrim().lower()
	if lowname[ .. 6] = "http://"	return true
	if lowname[ .. 5] = "ftp://" 	return true
	if lowname[ .. 7] = "https://"	return true
return false

boolean isHTML(text name)
    text ext = Path.getExtension(name)
    if ext in extensions return true     
return false

void linkchecker(text page)
    DOMNode current = null
    DOMElement elem = null
    boolean xres
    text link
    text base
    int resnum
  
    array links = {}
  
    if not isHTML(page) return
    
    page = convertUnix(page)
    text root = convertUnix(source)

    if VERBOSE print "Scanning $page"
    base, link = Path.splitFile(page)
    base = str_replace(root, website, base)
 
    DOMDocument d = DOMDocument()
    ~~
    @$xres = $d->loadHTMLFile($page);
    ~~
    if xres = false return 
 
    DOMNodeList dnl = d.getElementsByTagName("a")
    if dnl.length = 0 return 
    for int i in 0 .. dnl.length
        current = dnl.item(i)
        if current = null continue
        elem = current

        if elem.hasAttribute("href")
            link = elem.getAttribute("href")
            if link[0] = "#"  continue
            if link[0] = "/"  let link = Path.merge(website, link)
            int p = strpos(link, "#", 0)
            if p <> 0
                link = link[0 -- p]
            /if
            if not hasProtocol(link)
                if link.length() > 11
                    if link[ .. 10] = "javascript:"
                        if DEBUG print "Skipped javascript." 
                        continue
                    /if     
                /if
                if link.length() > 7
                    if link[ .. 6] = "mailto:"
                        if DEBUG print "Skipped mailto." 
                        continue
                    /if     
                /if
                link = Path.merge(base, link)   // build the URL
            /if
            links.push(link)
        /if
    /for
    
    if links.size() = 0 return  
    boolean HEADFLAG = true
    for link in links
        if link[ .. 7] = "https://"	
            resnum = httpsAccess(link)
        else
            resnum = httpAccess(link)
        /if    
        
        if resnum = 200 continue
        if resnum = 302 continue
        if HEADFLAG
            print page
            print "-".dup(page.length())        
            HEADFLAG = false        
        /if
        if resnum 
        = 404: print "Broken $link"
               brocount + 1 
        = 301: print "Redirect $link"
        else
            print "$resnum $link"
        /if    
    /for    

return

// Display list of broken links

void dispBroken()
    if brocount = 0 return
    echo brocount, " broken link", plural(brocount), ".\n"   
return


void linkCheckerDiffered(text page)
    pagesToCheck.push(page)
return

void differedCheck()
    if not CHECKLINKS return
    print "\nChecking links..."
    for text t in pagesToCheck
        linkchecker(t)
    /for    
return    

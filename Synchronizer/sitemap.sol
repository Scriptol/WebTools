# Sitemap extension to PHP FTP Synchronizer
# (c) 2016 Denis Sureau
# www.scriptol.com
# Licence LGPL

# Format compatible with sitemap.org and all search engines

include "dom.sol"
include "path.sol"

extern bool QUIET
extern text website


DOMDocument xmlmap
DOMElement urlset = null
array urlList = []
text dateFormat = "Y-m-d\\TH:i:sP"
text mapname = "sitemap.xml"
array sitemapExtensions = [ ".html", ".php", ".htm", ".asp" ] 
text mapremote = ""
array mapInArray = []
int mapSize = 0

void newMap()
    xmlmap =DOMDocument( "1.0", "UTF-8" )
    urlset = xmlmap.createElement("urlset")
    urlset.setAttribute("xmlns", "http://www.google.com/schemas/sitemap/0.84")
    urlset.setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance")
    urlset.setAttribute("xsi:schemaLocation", "http://www.google.com/schemas/sitemap/0.84 http://www.google.com/schemas/sitemap/0.84/sitemap.xsd");
    xmlmap.appendChild(urlset)	
return

# Load sitemap, get urlset element.
# Or create a new map if not found.

void loadMap()
    xmlmap.formatOutput = true
    xmlmap.preserveWhiteSpace = false
    bool e = xmlmap.load(mapname)
    if e = true 
        DOMNodeList dnl = xmlmap.getElementsByTagName("urlset")
        if dnl.length > 0
            urlset = dnl.item(0)
            return
        /if    
    /if    
    newMap()
return


void saveMap()
    xmlmap.save(mapname)
return

void XML2Array() 
    DOMNodeList selection = xmlmap.getElementsByTagName("loc")
    for int i in 0 -- selection.length
        DOMNode n = selection.item(i)
        mapInArray.push(n.nodeValue)
    /for    
    mapSize = mapInArray.size()
return 

bool inMap(text url)
    if url in mapInArray return true
return false


# Build an url tag
# Add to urlset or
# replace existing tag with same url,
# update date, keep frequency and priority

void addTag(text url, text linktime)
    DOMElement urltag = xmlmap.createElement("url")
    var actest = false
    var priority = "0.2"
    var frequency = "monthly"
    if inMap(url)
        DOMNodeList dnl = xmlmap.getElementsByTagName("loc")
        for int i in  0 -- dnl.length
            DOMElement e = dnl.item(i)
            if e.nodeValue <> url continue
            DOMNode oldtag = e.parentNode
            DOMNode fc = oldtag.firstChild
            while(fc)
                text name = fc.nodeName
                text val = fc.nodeValue
                if name = "changeFreq" let frequency = val
                if name = "priority"  let priority = val
            let fc = fc.nextSibling
            actest = urlset.replaceChild(urltag, oldtag)
            print "Updated $url in sitemap."
            break
        /for
    else    
        actest = urlset.appendChild(urltag)
        print "Added $url to sitemap."
        mapSize + 1
    /if
    if actest = false
        print "Error, can't append url to urlset."
        return
    /if 
    
    DOMElement loctag = xmlmap.createElement("loc", url)
    DOMElement modtag = xmlmap.createElement("lastmod", linktime)
    DOMElement ftag = xmlmap.createElement("changeFreq", frequency)
    DOMElement ptag = xmlmap.createElement("priority", priority)
    urltag.appendChild(loctag)
    urltag.appendChild(modtag)
    urltag.appendChild(ftag)
    urltag.appendChild(ptag)

return

// Add all urls to the site map
// from the list in urlList

# Convert local to URL and to unix

text setURL(text name)
	for int i in 0 -- name.length()
		if name[i] = "\\" let name[i] = "/"
	/for
return name	

void updateMap()
    if urlList.size() < 1 return
    if not QUIET print "\nUpdating sitemap:", urlList.size(),"links to add/update."    
    loadMap()
    XML2Array()
    for text url in urlList
	    url = setURL(url) 	// convert to slashes
	    if not hasProtocol(url)
		    url = Path.merge(website, url)
	    /if	
        text urlTime = date(dateFormat)	    
        addTag(url, urlTime)
    /for
    saveMap()
    if not QUIET print mapSize,"links in sitemap."
return

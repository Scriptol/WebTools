#
# Feed - Scriptol Library
# http://www.scriptol.com/compiler/
# Licence: LPGL
# Add a document to an RSS feed
# Created from the PHP RSS Reader
# http://www.scriptol.com/rss/rss-reader.php
#
# (c) 2008-2009 by Denis Sureau. Scriptol.com
#

include "feedrss.php"
include "linkcheck.sol"         // need for the website variable

extern
    array RSS_Channel                   // array of the channel
    array RSS_Retrieve(text)            // array of arrays of items
    void buildRSS(text, array, array)   // rebuild or create the file
/extern

text FEEDNAME = "/rss.xml"
int FEEDSIZE = 15
boolean RSSFEED = false

// Create a channel

void createFeed(text title, text desc)
    text date = date()
    
    RSS_Channel["title"] = title
    RSS_Channel["link"] = website
    RSS_Channel["description"] = desc
    RSS_Channel["date"] = date()

return


// Return the indice of the older post among new ones

int older(array urls)
    int indice = 0
    text older = ""
    
    if urls.size() = 1 return 0
    
    for int i in 0 .. urls.size()
        array x = urls[i]
        text date = x["date"]
        if date > older
            indice = i
            older = date
        /if    
    /for
        
return indice

// add all the last post to the array of the feed

void updateFeed(array urls)
    array feed = RSS_RetrieveLinks(FEEDNAME)
    int fsize = feed.size()
    int usize = urls.size()
    
    if usize >= FEEDSIZE
        feed = {}
        
    /if
    
    while not urls.empty()
        int i = older(urls)
        array x = urls[i]
        feed.unshift(x)
        urls[i .. i] = nil
    /while 
    
    feed = feed[ .. FEEDSIZE]

    buildRSS(FEEDNAME, RSS_Channel, feed)   // write the file

return


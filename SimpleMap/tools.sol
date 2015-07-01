
include "url.sol"


# remove leaving slahs or backslash

text noSlash(text pth)
	text c = pth[ -1 ..]
	if (c = "/") or (c = "\\") return pth[ .. -2]
return pth

/**
 *	Replace / by Windows's antislash
 */	 

text setWindows(text name)
	for int i in 0 -- name.length()
		if name[i] = "/" let name[i] = "\\"
	/for
return name	


# convert local to URL and to unix

text setURL(text name)
	for int i in 0 -- name.length()
		if name[i] = "\\" let name[i] = "/"
	/for
return name	


text textToUTF8(text content)
	content.replace("&", "&amp;")
	content.replace("<", "&lt;")
	content.replace(">", "&gt;")
return content


# if drive letter in path, change drive

void changeDir(text pth)
    if pth = nil return
    if DEBUG print "Moving to $pth"
	boolean t = @chdir(pth)
	if DEBUG or VERBOSE
	   if t = true
		  print "Now path is" , getcwd()
	   else
	      print "Error, enable to go to $pth from", getcwd()
       /if 	  
	/if	
return


# Check if the source ends with the string search

boolean endWith(text source, text search)

	text last =  search[-1 ..]
	if (last = "/") or (last = "\\") let search = search[ .. -2 ]

	int lsea = search.length()
	int lsrc = source.length()

	if lsrc < lsea return false
	if source[- lsea .. ] = search return true
	
return false	


# End with "\"

boolean isDirectory(text source)
	if source = nil return true
	text last =  source[-1 ..]
	if (last = "/") or (last = "\\") return true
return false



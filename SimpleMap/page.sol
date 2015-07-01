
include "path.sol"

class PageWeb

	text page
	int headstart
	int headend
	text heading = ""
	text filename = ""

	void load(text path)
		//print "loading", path, Path.exists(path), Path.isFile(path)
		if not Path.exists(path) return
		if not Path.isFile(path) return
	
		//print "opening", path
		//if(path = "tld-internet.php") print path.upper()
		file f = fopen(path, "r")
		int fs = filesize(path) 
		page =	f.read(fs)
		f.close()
		filename = path

	return
	

	
	text getMetaValue(text tagname)
	
		text name
		text value
		int metastart
		int starting
		int ending
	
		tagname = tagname.lower()
		
		int start = heading.findLex("meta")
		
		//print "start=", start
		
		if start = nil return ""
		
		while start < heading.length()
		
			metastart = heading.findLex("name", start)
			if metastart = nil return ""
			
			starting = heading.find("\"", metastart)
			if starting = nil let starting = heading.find("\'", metastart)
			if starting = nil return ""
			
			starting + 1
			ending = heading.find("\"", starting)
			if ending = nil let ending = heading.find("\'", starting)
			if ending = nil return ""
			
			name = heading[starting -- ending]
			name = name.trim()
			
			if name <> tagname continue
			
			starting = heading.findLex("content", ending + 1)
			if starting = nil return ""
			
			starting + 7
			starting = heading.findLex("=", starting)
			if starting = nil return ""
			
			metastart = starting
			
			starting = heading.find("\"", metastart)
			if starting = nil let starting = heading.find("\'", metastart)
			if starting = nil return ""
			
			starting + 1
			
			ending = heading.find("\"", starting)
			if ending = nil let ending = heading.find("\'", starting)
			if ending = nil return ""
			
			value = heading[starting -- ending]
			value = value.trim()
			//print "value=", value
			break
			
		let start + 4	
		
	return value	
	
	void getHead()
		boolean state = true
		heading = ""

		//print "PAGE", page.length()
		
		int starting = page.findLex("<head")
		if starting = nil return 
		
		starting = page.find(">", starting)
		if starting = nil return 
		
		int ending = page.findLex("</head")
		if ending = nil return 
		
		//print starting, ending
		
		headstart = starting
		headend = ending
		
		heading = page[headstart + 1 -- headend]
		
		//print heading
	
	return 

	boolean indexable()

		getHead()
		if heading = nil return true	// no head section, keep it
		text rob = getMetaValue("robots")
		if rob = nil return true	// no meta robot, indexable
		
		if rob.findLex("noindex") <> -1 return false
		if rob.findLex("none") <> -1 return false
	
	return true
	
	
/class	

/*
int main()

	PageWeb page
	page.load("e4x.html")
	
	if not page.indexable()
		print "no index"
	else
		print "index"	
	/if	

return 0

main()
*/

boolean checkIndexable(text fname, array skipped)
	PageWeb pw
	text localname
	boolean flag
	localname, flag = localPath(fname)
	
	if localname in skipped return false
	
	pw.load(localname)
	if not pw.indexable()
		print localname, "skipped, NOINDEX"
		skipped.push(localname)
		return false
	/if	 
return true

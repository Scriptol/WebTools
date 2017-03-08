
#  Path Class
#  Scriptol - (c) 2001-2016  D.G Sureau
#  www.scriptol.com
#  Licence: OSS

#  This is a set of static functions related to files in directory
#  The path separator is "/" under Unix and Windows


class Path

# EXISTS - Test if a file exists

static boolean exists(text dname): return file_exists(dname)


# SIZE - Return the size of a file

static number size(text fname):   return filesize(fname)


# TYPE - Return the type of an entry: file, dir, etc...

static text type(text fname):   return filetype(fname)

# DATE - Returns the date of a file

static text created(text fname):
    int t = filemtime(fname)
return date("",t)


# ISFILE
 
static boolean isFile(text fname) return filetype(fname) = "file"


# ISDIR
 
static boolean isDir(text fname)
	text t = filetype(fname)
	if t = "link"  return false
	if t != "dir"  return false
return true


# REN
 
static boolean ren(text oldname, text newname)
	boolean b = true
	rename(oldname, newname)
return b

# DELETE
 
static boolean erase(text fname) return unlink(fname)


# MERGE - Merge elements of path

static text merge(text path, text filename)
	if path="" return filename
	if filename = ""  return path
  text plc = path[path.length()-1]
  text ffc = filename[0] 
	if (plc <> "/") and (ffc <> "/") and (plc <> "\\") and (ffc <> "\\") let path + "/"
return path + filename


# MAKE DIR - Create a sub-directory

static boolean make(text name) return mkdir(name)


# SPLIT EXT - Split the node and the extension of a filename or path

static text, text splitExt(text path)
	int l = path.length()
	if l = 0 ? return "", ""
	for int x in l - 1 .. 0 step -1
		if path[x] = "." ? return path[--x], path[x + 1..]
	/for
return path, ""

# HAS EXTENSION - Test if the file has an extension or it is inside a list
# the list is an array of extensions separated by a space (with or without dot)

array nullarr = array()

static boolean hasExtension(text path, array extlist = [])
	int pos = path.findLast(".")
	if pos = nil return false
   
  	text longext = path[pos ..]
	text shortext = longext[1 ..]   // extension without dot
  
	if shortext = "" return false
	if extlist = nil return true    // no list provided, return true
	
	if shortext in extlist return true
	if longext in extlist return true
return false		


# GET EXTENSION - Get extension of a filename or path

static text getExtension(text path)
	int pos = path.findLast(".")
	if pos <> nil return path[pos ..]
return ""


# CHANGE EXTENSION - Replace current extension by given on
# on filename or full path

static text changeExt(text path, text newext = "")
	int l = path.length()
	if l = 0 return newext
	int pos = path.findLast(".")
	if pos <> nil 
    	if newext[0] ="." 
      		path = path[ -- pos]
    	else
    	  	path = path[ ..pos]
    	/if  
	/if      
return path + newext


# HAS DIR  - Return true if the path has a directory or dir

static boolean hasDir(text path)
	int l = path.length()
	if l = 0 ? return false
	if l > 1
		if path[1] = ":" return true
	/if	

	// Check if slash or anti-slash in string but leading or trailing ones
	if path.find("/") <> nil return true
	if path.find("\\") <> nil return true
return false


# SPLIT  - Split path to directory and file

static text, text splitFile(text path)
	int l = path.length()
	if l = 0  return "",""
	for int x in l - 1 .. 0 step - 1
		if (path[x] = "/") or (path[x] = "\\")  return path[..x], path[x + 1..]
	/for
return "", path


# GET DIR Get current directory
 
text getDir()  return getcwd()

# COMPARE PATHS

static boolean compare(text a, text b)
	int l = a.length()
	if l <> b.length() return false
	for int i in 0 -- l
		if (a[i] = "\\") or (a[i] = "/")
			if b[i] = "/"   continue
			if b[i] = "\\" continue
			return false
		/if
		if a[i] <> b[i] return false
	/for
return true


/class


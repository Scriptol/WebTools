# PHP FTP Synchronizer 
# (c) 2007-2015 Scriptol.com. By Kim Haskell & Denis Sureau
# Free under the GNU GPL 2 License.
# Requires the PHP 5 interpreter.
# Sources are compiled with the Scriptol to PHP compiler version 7.0.
# Libraries (c) by Denis Sureau
# www.scriptol.com 
#
# The synchronizer updates a website from a local directory.
# - It is able to use techniques to increase the speed.
# - Optionally links in the pages sent are checked.
# Read the manual for details of use.
#

include "path.sol"
include "ftp.sol"
include "linkcheck.sol"
//include "feed.sol"

boolean CHECKMODE = false   // True for virtual operations
boolean QUIET = false     // True to display nothing
boolean BACKUP = false    // True to work with a backup directory
boolean ANYFILES = false  // restoring the site and uploading the full content
boolean TOUCHFLAG = true  // server of back supports the touch function (accelarator)
boolean CONTFLAG = false  // compare by content, not by time
boolean DAYSFLAG = false  // upload files changed within n days
boolean SKIPPED = false   // display skipped files

int days = 0        // Number of past days to handle for updating 
text server = "" 	// The ftp address
text user = ""		// login
text pass = ""		// password
array params = []
text backdir = ""   // backup directory or drive
text temporary = "temporary-file.000.tmp"

int connection = 0	// handler
int counter         // Number of files uploaded
int falsecounter    // Number of files to copy
int problem



void usage()
	print
	print "FTP Synchronizer 2.0 - (c) 2007-2015 Scriptol.com"
	print "-------------------------------------------------"
	print "Syntax:"
	print "  solp ftpsync [options] source ftpadr"
	print "Options:"
	print "  -t test, display only and do nothing."
	print "  -v verbose, display more infos."
	print "  -q quiet, display nothing."	
	print "  -a all files, restore the full site"
	print "  -c compare contents, ignore time"
	print "  -w website url (for the link checker)."
	print "  -ndays number of days to upload."
	print "  -ppassword."
	print "  -llogin."
	print "  -fftpadr ftp address in any form."
	print "  -ddirectory remote directory where to upload the files."
	print "  -bbackup, defining a backup directory"  
    print "Extended options"
	print "  -u activate the link checker."
    //print "  -r activate the RSS generator. Name and size optional."	
	print "  -k display skipped files."
    print "Arguments:"
	print "  source: a directory to backup"
	print "  ftpadr: remote adr in the form ftp.domain.tld (as ftp.scriptol.com)"
	print "You will be prompted for each omitted but required parameter."
	print "Optionally you will be prompted to add a valid document as RSS item."
	print "See manual for compatibily between options."
	exit(0)
return


boolean syncConnect()
	connection = ftp_connect(server)
	if connection = 0 let die("Not connected")
	
	if ftp_login(connection, user, pass) = true
		print "Connected on $server as $user"
		if ftp_pasv(connection, true) = true
		  print "Passive mode turned on"
		else
      print "Enable to set passive mode"
    /if    
		return true
	else	
		print "Enable to connect as $user on $server"
	/if
return false

void syncDisconnect()
	ftp_close(connection)
return

// size of a remote file

int syncSize(text fname)
return ftp_size(connection, fname)	

int syncTime(text fname) 
return ftp_mdtm(connection, fname)


boolean filecompare(text a, text b)
	array x, y
	x.load(a)
	y.load(b)
return x = y	


// Check the presence of the external device or the existence of the directory
// for the backup.

void backError(text b)
  print "Can't write on backup $b, check device or path and try again..."
  exit(0)
return  
  

void checkBackup(text bpath)
  text tempfile = Path.merge(bpath, "ftpsynxyz.$$$")
  file f 
  f.open(tempfile, "w")
  error ? backError(bpath)
  int saved = f.write("ftp synchro")
  f.close()

  if saved = 0
    backError(bpath)
  else
    if CONTFLAG
      print "Files compared by content"
      TOUCHFLAG = false
      return 
    /if
    TOUCHFLAG = touch(convertUnix(tempfile), time())
    unlink(tempfile)
    if not QUIET 
      if TOUCHFLAG 
        print "Files compared by time"
      else
        print "Touch failed, files compared by contents"
      /if    
    /if  
  /if
return

void checkRemote(text rpath)
  TOUCHFLAG = touch(rpath, time())
return         


// send a file

void filecopy(text src, text rmt, text loc)

	if CHECKMODE = true
		print "Must upload $src in $rmt"
		falsecounter + 1
		return
	/if
	
	if QUIET  = false
		echo "Uploading $src "
        if DAYSFLAG
            print date('Y-m-d', filemtime(src))
        else
            print "to $rmt"
        /if 
	/if

    if CHECKLINKS let linkCheckerDiffered(src)
    boolean putres
    ~~
        try {
    ~~     
    putres = ftp_put(connection, rmt, src, $(FTP_BINARY))
    ~~
    } catch(Exception $e) { ;  }
    ~~
    if putres = true
		counter + 1
		if BACKUP = true  
            copy(src, loc)
            if loc = nil return     // No backup file
            // Does not work under older versoins of Windows
            boolean b = @touch(convertUnix(loc), filemtime(src))  // set same date and time now
            if VERBOSE
                if b            
                    print "Updated date and time for", convertUnix(loc)
                else
                    print "Failed to change time for", convertUnix(loc)
                /if  
            /if
        /if
	else
		print "Error, $src not uploaded"
	/if	
return


boolean remoteIdentical(text lfile, text rfile)
  if DEBUG = true print "Comparing $lfile and remote $rfile"
	if @ftp_get(connection, temporary, rfile, $(FTP_BINARY)) != true return false
	array x, y
	x.load(lfile)
	y.load(temporary)
return x = y


// compare file with backup

boolean backupIdentical(text locfile, text bakfile)
  array x, y
  if DEBUG = true print "Comparing $locfile and local $bakfile"
  if not file_exists(bakfile) return false
	if filesize(locfile) <> filesize(bakfile) return false
	if TOUCHFLAG
	 int a = filemtime(locfile)
	 int b = filemtime(bakfile)
	 if a = b return true
	/if
	x.load(locfile)
    y.load(bakfile)
  //print "Comparing $locfile", filesize(locfile), "and local", bakfile, filesize(bakfile), x = y
return x = y


// compare date of file with number of days 

boolean dateCompare(int loctime, int numdays)
  numdays + 1
  int nt = time() - (86400 * numdays)
return loctime >= nt 


// synchronize

void synchro(text locdir, text bdir, text hostdir)

	array content = scandir(locdir)
	text src, bck, rmt
	boolean returned
	
	if hostdir <> nil
	  if VERBOSE or CHECKMODE 
      echo "Creating $hostdir if needed"
      if BACKUP echo ", and $bdir"
      print
    /if      
	  
    if not CHECKMODE 
      @ftp_mkdir(connection, hostdir)
      if BACKUP = true
        if not file_exists(bdir) let @mkdir(bdir)
      /if 
    /if
	/if	
	
	if content.empty() return
	
	// processing files
	
	for text name in content

        if src[ .. 10] = "javascript:" continue

        if name[0] = "/"
            src = Path.merge(website, name) 
        else    
            src = Path.merge(locdir, name)
		/if  
		if VERBOSE print "Processing $src"
		
		if filetype(src) = "file"
			rmt = Path.merge(hostdir, name)
			
			// Scan page for checking links
			
			if ANYFILES = true     // uploading the full content
			    filecopy(src, rmt, "")
				continue
			/if

            // .htaccess and such files can't be read and are ignored
                     
            if name[0] = "."     
                if not QUIET print name, "skipped"
                problem + 1
                continue 
            /if
			
            // compare with backup and upload if different
    
            if DAYSFLAG = true
                if dateCompare(filemtime(src), days)
                    if BACKUP = true
                        bck = Path.merge(bdir, name)
                        filecopy(src, rmt, bck)
                    else
                        filecopy(src, rmt, "")    
                    /if
                else
                    if SKIPPED print "  Skipped ", src    
                /if
                continue
            /if       
      
           if BACKUP = true 
                bck = Path.merge(bdir, name)
                returned = backupIdentical(src, bck)
                if not returned
		          filecopy(src, rmt, bck)
		        else
                  if SKIPPED print "  Skipped ", src    
            	/if
                continue
            /if
                     
            // compare with remote file and upload if different
            returned = remoteIdentical(src, rmt)
            if not returned
                filecopy(src, rmt, "")
            else
                if SKIPPED print "  Skipped ", src    
	       /if
		/if
	/for

	// processing subdirs
	
	for text name in content
	    if name[0] = '.' continue
		src = Path.merge(locdir, name)	
		if filetype(src) = "dir"
			synchro(src, Path.merge(bdir, name), Path.merge(hostdir, name))
		/if
	/for	

return

boolean readLogin()
	array loglist
	loglist.load("ftpsync.login")
	for text line in loglist
		if server in line
			array data  = line.split(" ")
			user = data[1]
			pass = data[2]
			return true
		/if
	/for	
return false	


// Parsing command line parameters
// Stored into an array to overcome problems with PHP's global variables

void processCommand(int argnum, array arguments)

    text daystring = ""
	text opt

	if argnum <  2
		usage()
	/if	


	for text param in arguments

		if param.length() > 1
			opt = param[..1]
		else
			usage()
		/if	
		
		if opt = "-t" 
			CHECKMODE = true
			continue
		/if	

		if opt = "-a" 
			ANYFILES = true
			continue
		/if	

		if opt = "-v" 
			VERBOSE = true
			continue
		/if
        
       	if opt = "-k" 
			SKIPPED = true
			continue
		/if		

		if opt = "-q" 
			QUIET = true
			continue
		/if	

		if opt = "-~" 
			DEBUG = true
			continue
		/if
    
        if opt = "-c"
            CONTFLAG = true
            continue
        /if  
        
		if opt = "-u"
            CHECKLINKS = true 
			continue
		/if
/*        
        if opt = "-r"
            text a, b
            RSSFEED = true
   			text x = param[ 2 .. ]
   			if x = "" continue
            int i = x.find(",")
            if i = -1
               a = x[.. i]
               b = x[i + 1 ..]
            else
               a = x
               b = ""
            /if
            if intval(a) <> 0
                FEEDNAME = a
                if intval(b) <> 0
                    FEEDSIZE = intval(b)
                /if    
            else
                FEEDSIZE = intval(a)
                if b <> "" let FEEDNAME = b
            /if               
            
            continue
        /if            	        	
*/
		if opt = "-p"
			pass = param[ 2 .. ]
			if pass = nil let die("-p must be followed by the password.")
			continue
		/if	

		if opt = "-l"
			user = param[ 2 .. ]
			if user = nil let die("-l must be followed by the login.")			
			continue
		/if

		if opt = "-f"
			server = param[ 2 .. ]
			if server = nil let die("-f must be followed by the ftp address.")			
			continue
		/if

		if opt = "-w"
			website = param[ 2 .. ]
			if website = nil let die("-w must be followed by the site url.")			
			continue
		/if


		if opt = "-n" 
			daystring = param[ 2 .. ]
			if daystring = "" let die("-n requires a number of days.")
			days = daystring.toInt()
			DAYSFLAG = true
			continue
		/if	

		if opt = "-d" 
			remotedir = param[ 2 .. ]
			if remotedir = nil let die("-d requires a sub-directory.")
			continue
		/if	
    	
		if opt = "-b"
			backdir = param[ 2 .. ]
			if backdir = nil let die("-b requires a directory.")
			BACKUP = true
			continue
		/if
    	
		if param[ .. 3] = "ftp."
			server = param
			continue
		/if	
		
		if param[0] = "-" 
        print "Unknown command $param"  
        usage()
    /if   
		
		if source = nil
			source = param
			continue
		/if	
		
		print "Unknown command $param"
    
    usage()
		
	/for


  if BACKUP = true let checkBackup(backdir)

	if server = nil input "FTP location: ",  server
	if server = nil let exit(0)

	if source = nil input "Directory to send: ",  source
	if source = nil let exit(0)

	if user = nil input "Login: ",  user
	if user = nil let exit(0)

	if pass = nil input "Password: ", pass	
	if pass = nil let exit(0)
	
	params["server"] = server
	params["user"] = user
	params["pass"] = pass
	params["source"] = source
	params["backdir"] = backdir
	params["days"] = days
	params["remdir"] = remotedir
	params["website"] = website

return


int main(int argc, array argv)

	array x = argv[ 1 .. ]
	
	processCommand(argc, x)

    problem = 0
    server = params["server"]
    user = params["user"]
    pass = params["pass"]
    source = params["source"]
    backdir = params["backdir"]
    days = params["days"]
    website = params["website"]
    remotedir = params["remdir"]
    
    // Building the base URL for link check
    if website = nil
        // Convert ftp to http
        // If the URL does not include www then add a dot to ftp and remove www 
        website = preg_replace("/^ftp/i", "http://www", server, 1)
    else
        if not hasProtocol(website)
            website = "http://" + website
        /if
    /if  
    
    if not QUIET
        if VERBOSE = true print "Verbose mode enabled"
        if DEBUG = true print "Debug mode enabled"
        if DAYSFLAG
            echo "Update files changed "
            if days > 0
                print "within", $days + 1,"days"
            else
                print "last day"
            /if  
        /if     
        print "Source directory: $source"
        print "Remote directory:", remotedir
        if BACKUP = true print "Backup location: $backdir"
        if ANYFILES = true print "Website will be restored"
        if CHECKLINKS print "Link checker active"
    /if

	syncConnect()
	
	print "Synchronizing $source on $server"
	synchro(source, backdir, remotedir)		// starting at root or given remote path
	
	syncDisconnect()
	
	if QUIET return 0
	
	echo counter, " file", plural(counter), " copied"
	if CHECKMODE
        if falsecounter > 0 
            echo ", ", falsecounter, " file", plural(falsecounter)," to update"
        else
            echo ", nothing to update"
        /if
    /if      
	print "."
	if problem > 0 print "$problem file" + plural(problem) , "skipped."
	
	if CHECKLINKS 
       differedCheck()
       if counter > 0 let dispBroken()
    /if    
	
return 0

main($argc, $argv)

# FTP Checker 1.1
# (c) 2007-2016 By Kim Haskell/Denis Sureau
# Requires the PHP interpreter.
# Sources are compiled with the Scriptol to PHP compiler version 7.0.
# www.scriptol.com 
#
# The script checks your ftp connection:
# - You can upload a file.
# - You can change date for a file (this may help to update).


include "path.sol"
include "ftp.sol"

boolean DISPLAY = false   // True for virtual operations

text server = ""    // url parameter
text source = ""	// local directory at start
text user = ""		// login
text pass = ""		// password
array params = {}
text temporary = "temporary-file.000.temp"
int connection = 0	// handler

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

void syncDelete(text fname)
  boolean x = ftp_delete(connection, fname)
  if x = true
    print fname, "deleted"
  else
    print "Enable to delete", fname  
  /if  
return  


// Check upload

text checkUpload(text src, text subdir)

    text remfile = Path.merge(subdir, src)
    
    print "Uploading $src to $remfile"
	if ftp_put(connection, remfile, src, $(FTP_BINARY)) = true
        print "$src successfully uploaded"
	else
		print "Error, $src not uploaded"
	/if	
	
return remfile


void usage()
	print
	print "FTP Check 1.0 - (c) 2007-2016 Kim Haskell Scriptol.com"
	print "------------------------------------------------------"
	print "Syntax:"
	print "  php ftpcheck.php [options] source [ftp]"
	print "Options:"
	print "  -ppassword."
	print "  -llogin."
	print "  -ddirectory"
	print "Arguments:"
	print "  source: a file to upload"
	print "  ftp: remote adr in the form ftp.#ain.tld (as ftp.scriptol.com)"
	print "If filename is ommitted, the default $temporary file is used"
	print "You will be prompted for each other omitted parameter."
	exit(0)
return


// Parsing command line parameters
// Stored into an array to overcome problems with PHP's global variables

void processCommand(int argnum, array arguments)

	text opt
	text remotedir = ""
	
	source = nil

	if argnum <  2 ? usage()

	for text param in arguments
		if param.length() > 1
			opt = param[..1]
		else
			usage()
		/if	
		
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

		if opt = "-d" 
			remotedir = param[ 2 .. ]
			if remotedir = nil let die("-d requires a sub-directory.")
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


	if server = nil input "FTP location: ",  server
	if server = nil let exit(0)

	if source = nil let source = temporary
	
	if not file_exists(source) let die("File $source not found.")

	if user = nil input "Login: ",  user
	if user = nil let exit(0)

	if pass = nil input "Password: ", pass	
	if pass = nil let exit(0)
	
return

int main(int argc, array argv)
	array x = argv[ 1 .. ]
	processCommand(argc, x)
	syncConnect()
	text filename = checkUpload(source, remotedir)
	syncDelete(filename)
	syncDisconnect()
return 0

main($argc, $argv)

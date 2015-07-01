
// FTP header file for the Scriptol PHP compiler 
// Detail of functions at http://www.php.net/manual/en/

extern 
  
  int ftp_connect(cstring)                    // ftp url
  boolean ftp_login(int, cstring, cstring)    
  boolean ftp_put(int, cstring, cstring, int, int=0)    // upload
  boolean ftp_get(int, cstring, cstring, int, int=0)    // download
  boolean ftp_close(int)
  boolean ftp_pasv(int, boolean)
  
  int ftp_nb_get(int, cstring, cstring, int, int=0) // asynchronous
  int ftp_nb_put(int, cstring, cstring, int, int=0) // asynchronous
  
  boolean ftp_chdir(int, cstring)
  cstring ftp_mkdir(int, cstring)
  cstring ftp_pwd(int)                  // current directory name
  array ftp_nlist(int, cstring)         // list of the files in the directory
  cstring ftp_systype(int, cstring)     // system type, UNIX, etc.
  
  boolean ftp_delete(int, cstring)
  int ftp_chmod(int, int mode, cstring)
  boolean ftp_rename(int, cstring, cstring)   // handler, old name, new name
  int ftp_size(int, cstring)      // size of a file
  int ftp_mdtm(int, cstring)		// last modified date
  
  boolean ftp_exec(int, cstring)
  
  boolean touch(cstring, int)

/extern  

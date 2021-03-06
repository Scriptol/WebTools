
extern

//===== types

boolean is_array(var a)

//===== numbers

number doubleval(string)
int intval(string)


//===== array

array array_merge(array, array)
array array_diff(array, array)
array array_intersect(array, array)
array array_unique(array)
int   array_search(array, string)
dict array_reverse(dict, boolean)

void sort(array)
void asort(array)
void ksort(array)

int  count(array)

text implode(array, string)

boolean in_array(var, array)
boolean in_sequence(string, string)


var reset(array)
var next(array)
var prev(array)
var end(array)
text key(array )
var current(array )

void store(array, string)
dict get_meta_tags(string)

//===== text

text strval(var)

char *strtoupper(string)
char *strtolower(string)
int  strcasecmp(string, string)
array explode(string , string)
char *str_replace(string, string, string)
char *strtr(string, string, string)
char *strstr(string, string)
char *stristr(string, string)
char *strchr(char *, byte)
char *strrchr(char *, byte)
int strpos(string, string, int)
char *rtrim(string)
char *ltrim(string)
char *trim(string)
char *chop(string)
text str_pad(string, int, string = " ", int = 1)
char *str_repeat(string, int)
int substr_count(string, string)
char *substr_replace(string, string, int, int)
char *ucfirst(string)
char *ucwords(string)
char *wordwrap(string, int, string);


//===== file

file fopen(string, string)
void fseek(file, int)
void fputs(string)
text fgets(file)
text fread(file, int size)
int  fwrite(file, string)

void gets(string)

int binOpen(cstring, cstring)
cstring binRead(int, int)
int binWrite(int, cstring, int)
int binClose(int)

//===== dir

int  filemtime(string)
const char *date(string, int)
text type(cstring)

//===== regular not yet implemented


/extern


enum STR_PAD_LEFT, STR_PAD_RIGHT, STR_PAD_BOTH
enum IMG_GIF, IMG_JPG:2, IMG_PNG:4, IMG_WBMP:8

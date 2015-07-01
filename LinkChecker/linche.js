/* PHP Socket and WebSocket Demo - Scriptol.com */  

var uri="/interface.html";
var phpscript = "linche.php";

var fs = require('fs'),
    http = require('http'),
    net = require('net'),
    path = require("path"),  
    url = require("url"),  
    runner = require("child_process");  
    websocket = require("socket.io");

var page = fs.readFileSync(__dirname + uri);  // file of the interface

function runScript(exists, file, param)
{
  if(!exists)
  {
    console.log(file + " not found");
    return false;
  }  
  console.log("Running...");
  var r = runner.exec("php " + file + " " + param, 
    function(err, stdout, stderr) { console.log(stdout);}
  );
  console.log(file + " launched by the server...");
  r.on('exit', function (code) { 
    console.log('PHP script terminated.');
  });
}

var localpath="";
var param="";
function php(request, response)
{  
  var urlpath = url.parse(request.url).pathname;
  param = url.parse(request.url).query;    
  localpath = path.join(process.cwd(), urlpath);   
}


function handler(request, response)
{
  if(request.url == '/favicon.ico') return;
  response.write(page);   // displays the interface
  php(request, response); // run the php script
  response.end();         // exits
}


function webComm(websocket) 
{
  //websocket.emit('notification', 'Server online via websocket!');
  websocket.on('interface', 
    function (data) 
    {
      console.log("Launching PHP..." + localpath + " " + data);
      path.exists(localpath, function(result) { runScript(result, localpath, data)});
    }
  );
}

// create a JavaScript server and launch a native script - call webComm()
var app = http.createServer(function(r, s){ handler(r,s); });
app.listen(1000);
var listener = websocket.listen(app);
console.log("WebSocket started on port 1000.");
listener.sockets.on('connection', function (websocket) { webComm(websocket);} );

function nativeComm(native) 
{
    console.log('TCP connection: ' + native.remoteAddress +':'+ native.remotePort);
    native.setEncoding("utf8");

    native.on('data', 
      function(data) 
      {
        listener.sockets.emit('notification', data);
      }
    );
    native.on('end', 
      function() { 
        console.log('PHP connection closed.');  
        //listener.sockets.emit('notification', 'PHP script terminated.');
        }
    );  
}    

// Create a TCP server to communicate with native script - call nativeComm
var nativeserver = net.createServer(function(native) { nativeComm(native);});
nativeserver.listen(1001, '127.0.0.1');
console.log('TCP local server active on port 1001.');
/**
	PHP Implementation of W3C DOM official
	Fully compatible with PHP, requires Scriptol PHP
	This interface (c) 2007-2016 Denis Sureau - LPGL license
*/

extern

class DOMNode 
	text nodeName          // the tag name
	text nodeValue         // its content
	DOMNode firstChild
	DOMNode lastChild
    DOMNode previousSibling
    DOMNode nextSibling
	DOMNode parentNode
	DOMNode appendChild(DOMNode)
	DOMNode removeChild(DOMNode)
	DOMNode replaceChild(DOMNode, DOMNode)  // new is first parameter
/class

class DOMElement is DOMNode
	text textContent
	boolean hasAttribute(cstring)
	cstring getAttribute(cstring)
	boolean setAttribute(cstring, cstring)
	boolean removeAttribute(cstring)
/class

class DOMComment
/class

class DOMText
	DOMText splitText()
/class

class DOMNodeList
	DOMNode item(integer)
	int length             // the number of elements
/class

class DOMDocument
    DOMDocument DOMDocument(cstring = null, cstring = null)
    
    bool formatOutput
    bool preserveWhiteSpace

	boolean loadHTMLFile(cstring)	// Load a HTML file
	boolean loadHTML(cstring)		// Create HTML from the raw content of a string
	boolean load(cstring)		// Load an XML file
	boolean loadXML(cstring)		// Convert a string into XML. 
	var save(cstring)			// Save HTML or XML into a file.
	cstring saveHTML(cstring = null)	// Return a HTML document (save to string)
	cstring saveXML(cstring = null)	// Return an XML document (save to string)
	
	DOMElement getElementById(cstring)
	DOMNodeList getElementsByTagName(cstring)
	
	boolean validate()		// Check using the DTD of the document
	
	DOMText createTextNode (cstring)
	DOMNode appendChild(DOMNode)
	DOMElement createElement(cstring, cstring = null)	// Create an orphan DOMElement, use appendChild
	DOMComment createComment(cstring)	// Create an orphan DOMComment

/class

class DOMXPath
    var evaluate(text, DOMNode = null, bool = true)
    DOMNodeList query(text, DOMNode = null, bool = true)
/class

/extern



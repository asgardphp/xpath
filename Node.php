<?php
namespace H0gar\Xpath;

/**
 * Xpath handler for a node.
 * 
 * @author Michel Hognerud <michel@hognerud.net>
*/
class Node {
	protected $domnode;
	protected $xpath;
	
	/**
	 * Constructor
	 * 
	 * @param \DOMNode|null domnode
	*/
	public function __construct($domnode) {
		if(!$domnode)
			return;
		$this->domnode = $domnode;
		$this->xpath = static::nodeToXpath($domnode);
	}
	
	/**
	 * Returns the parent node.
	 * 
	 * @return Node
	*/
	public function parent() {
		$parent = $this->domnode;
		if(!$parent)
			return new Node(null);
		while(true) {
			$parent = $parent->parentNode;
			if(!$parent)
				return;
			if($parent->nodeType == XML_ELEMENT_NODE)
				return new Node($parent);
		}
	}

	public function remove($path=null) {
		if($this->xpath === null)
			return null;
		if(!$path)
			$this->domnode->parentNode->removeChild($this->domnode);
		else {
			$items= $this->items($path);
			foreach($items as $item)
				$item->remove();
		}

		return $this;
	}

	/**
	 * Checks if node exists.
	 * 
	 * @return boolean
	 */
	public function exists() {
		return $this->xpath !== null;
	}
	
	/**
	 * Returns the previous node.
	 * 
	 * @return Node
	*/
	public function prev() {
		$prev = $this->domnode;
		if(!$prev)
			return new Node(null);
		while(true) {
			$prev = $prev->previousSibling;
			if(!$prev)
				return new Node(null);
			if($prev->nodeType == XML_ELEMENT_NODE)
				return new Node($prev);
		}
	}
	
	/**
	 * Returns the next node.
	 * 
	 * @return Node
	*/
	public function next() {
		$next = $this->domnode;
		if(!$next)
			return new Node(null);
		while(true) {
			$next = $next->nextSibling;
			if(!$next)
				return new Node(null);
			if($next->nodeType == XML_ELEMENT_NODE)
				return new Node($next);
		}
	}
	
	/**
	 * Returns the variable xpath (DOMXPath).
	 * 
	 * @return \DOMXPath
	*/
	public function getXpath() {
		return $this->xpath;
	}
	
	/**
	 * Returns the variable domnode (DOMNode).
	 * 
	 * @return \DOMNode
	*/
	public function getNode() {
		return $this->domnode;
	}
	
	public function tag() {
		if($this->domnode === null)
			return null;
		return strtolower($this->domnode->tagName);
	}
	
	/**
	 * Returns an attribute of the node.
	 * 
	 * @param string attr The attribute name.
	 * 
	 * @return string
	*/
	public function attr($attr) {
		if($this->domnode === null)
			return null;
		return $this->domnode->getAttribute($attr);
	}
	
	/**
	 * Returns the html for a specific children.
	 * 
	 * @param string path Path to the node.
	 * @param integer pos Element position.
	 * 
	 * @return string The html code of the node.
	*/
	public function html($path=null, $pos=0) {
		if($this->xpath === null)
			return null;
		if(!$path)
			return trim(static::getInnerHTML($this->domnode));
		else {
			$item = $this->item($path, $pos);
			if($item === null)
				return null;
			return $item->html();
		}
	}
	
	/**
	 * Returns the inner xml for a specific children.
	 * 
	 * @param string path Path to the node.
	 * @param integer pos Element position.
	 * 
	 * @return string The html code of the node.
	*/
	public function xml($path=null, $pos=0) {
		if($this->xpath === null)
			return null;
		if(!$path)
			return trim(static::getInnerXML($this->domnode));
		else {
			$item = $this->item($path, $pos);
			if($item === null)
				return null;
			return $item->xml();
		}
	}
	
	/**
	 * Returns the text for a specific children.
	 * 
	 * @param string path Path to the node.
	 * @param integer pos Element position.
	 * 
	 * @return string The text of the node.
	*/
	public function text($path=null, $pos=0) {
		if($this->xpath === null)
			return null;
		if(!$path)
			return trim($this->domnode->nodeValue);
		else {
			$item = $this->item($path, $pos);
			if($item === null)
				return null;
			return $item->text();
		}
	}

	public function ownText() {
		$texts = [];
		foreach($this->domnode->childNodes as $n) {
			if($n instanceof \DOMText) {
				$text = trim($n->textContent);
				if($text)
				$texts[] = $text;
			}
		}

		if($texts)
			return implode(' ', $texts);
		else
			return '';
	}
	
	/**
	 * Returns the first Node object from xpath.
	 * 
	 * @param string path Path to the node.
	 * @param integer pos Element position.
	 * 
	 * @return Node
	*/
	public function item($path, $pos=0) {
		if($this->xpath === null)
			return new Node(null);
		return new Node($this->xpath->evaluate($path)->item($pos));
	}
	
	/**
	 * Returns an array of children.
	 * 
	 * @param string path Path to the nodes.
	 * 
	 * @return array Array of Node objects.
	*/
	public function items($path) {
		if($this->xpath === null)
			return array();
		$r = array();
		$nodes = $this->xpath->evaluate($path);
		foreach($nodes as $n)
			$r[] = new Node($n);
		return $r;
	}
	
	/**
	 * Converts a DOMNode object to a DOMXPath object.
	 * 
	 * @param \DOMNode domnode
	 * 
	 * @return \DOMXPath
	*/
	protected static function nodeToXpath(\DOMNode $domnode) {
		$dom = new \DOMDocument();
		$dom->formatOutput = true;
		$node = $dom->importNode($domnode, true);
		if($node instanceof \DOMElement)
			$dom->appendChild($node);
		return new \DOMXPath($dom);
	}
	
	/**
	 * Returns the inner html of a DOMNode object.
	 * 
	 * @param \DOMNode|null node
	 * 
	 * @return string HTML code
	*/
	protected static function getInnerHTML($node) {
		if(!$node)
			return null;
		$innerHTML= ''; 
		$children = $node->childNodes; 
		foreach ($children as $child)
			$innerHTML .= $child->ownerDocument->saveHTML($child);
		return $innerHTML; 
	}
	
	/**
	 * Returns the inner xml of a DOMNode object.
	 * 
	 * @param \DOMNode node
	 * 
	 * @return string XML code
	*/
	protected static function getInnerXML($node) {
		if(!$node)
			return null;
		$innerXML= ''; 
		$children = $node->childNodes; 
		foreach ($children as $child)
			$innerXML .= $child->ownerDocument->saveXML($child);
		return $innerXML; 
	}

	public function getOuterHTML() {
		$doc = new \DOMDocument();
		$doc->appendChild($doc->importNode($this->domnode, true));
		$html = $doc->saveHTML();
		return $html;
	}

	public function getOuterXML() {
		$doc = new \DOMDocument();
		$doc->appendChild($doc->importNode($this->domnode, true));
		$html = $doc->saveXML();
		return $html;
	}

	public function children() {
		$children = [];
		foreach($this->domnode->childNodes as $child) {
			if($child instanceof \DOMElement)
				$children[] = new Node($child);
		}
		return $children;
	}
}
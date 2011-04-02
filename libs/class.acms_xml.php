<?php
/**
 * Version: GPL 2.0/LGPL 2.1
 *
 * The Original Code is Copyright (C)
 * 2004,2005,2006,2007 Tarmo Hyvarinen <th@angelinecms.info>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Hyvarinen <th@angelinecms.info>
 *
 * Portions created by the Initial Developer are Copyright (C) 2004,2005,2006,2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * 2004 Mikko Ruohola <polarfox@polarfox.net>
 * 2005,2006,2007 Joni Halme <jontsa@angelinecms.info>
 * 2005 J-P Vieresjoki <jp@angelinecms.info>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 */
/**
 * XML parser.
 *
 * Creates XML data from PHP objects. Objects can contain more objects, arrays, strings etc.
 *
 * @package    Kernel
 * @subpackage Xml
 * @uses       DomDocument
 * @uses       Common_Format
 * @todo       Mergin of two Xml_Xml instances to combine xml data? This would be cool since it is really GAY with DOM level 2.
 * @todo       Attribute support
 */
class Xml_Xml {

	/**
	 * Instance of DomDocument.
	 *
	 * @access public
	 * @var DomDocument
	 */
	protected $dom;
	/**
	 * Instance of Common_Format.
	 *
	 * @access private
	 * @var Common_Format
	 */
	private $format;
	/**
	 * XML root-element.
	 *
	 * @access public
	 * @var DOMElement
	 */
	protected $root;

	/**
	 * Constructor.
	 *
	 * Fires up Common_Format and DomDocument.
	 *
	 * @todo   Add support for multiple encodings
	 * @access public
	 * @param  string $rootname XML root element name
	 */
	function __construct($rootname=false) {
		if(!$rootname||!$this->validateNodeName($rootname)) {
			$rootname="root";
		}
		// FIXME
		//$encoding=config()->encoding();
		$encoding='utf-8';
		$this->dom=new DomDocument("1.0","{$encoding}");
		$this->dom->preserveWhiteSpace=FALSE;
		$this->dom->resolveExternals=FALSE;
		$this->dom->formatOutput=TRUE;
		$this->root=$this->dom->createElement($rootname);
	}

	/**
	 * Checks if strings is valid as XML node name.
	 *
	 * @access public
	 * @param  string $node Node name
	 * @return bool True if string can be used as node name, otherwise false.
	 */
	protected function validateNodeName($node) {
		if(empty($node)||is_numeric(substr($node,0,1))||substr(strtolower($node),0,3)=="xml"||strstr($node," ")) {
			// FIXME
			// IKU / 2007-05-26
			//DEBUG("'{$node}' is invalid");
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Returns XML data as string.
	 *
	 * @access public
	 * @return string
	 */
	public function parse() {
		$this->dom->appendChild($this->root);
		//if(config()->debug()==1) {
		// IKU / 2007-05-26
		if (defined('DEVEL')) {
			if(is_writeable("/tmp/xmltree.xml")) {
				$this->dom->save("/tmp/xmltree.xml");
			}
		}
		return (string)$this->dom->saveXML();
	}

	/**
	 * Returns DomDocument
	 *
	 * This differs from parse() because it replicates
	 * $this->dom instead of writing to it
	 *
	 * 2007-05-26 written by iku
	 *
	 * @todo   fix hard-coded TMPDIR
	 * @access public
	 * @return DomDocument
	 */
	public function returnDom()
	{
		// replicate
		$dom = $this->dom;
		$dom->appendChild($this->root);
		if (defined('DEVEL')) {
			if(is_writeable("../../tmp/xmltree.xml")) {
				$dom->save("../../tmp/xmltree.xml");
			}
		}
		return $dom;
	}

	/**
	 * Adds an object to XML tree.
	 *
	 * $object parameter can also be an array but array keys must be strings.
	 *
	 * @todo    Validate $object
	 * @access  public
	 * @param   object $object XMLVars object or array
	 * @param   boolean $cdata Defines if we create CDATA section to XMLtree
	 * @param   boolean $encode True/False if we want to run Common_Format::encode()
	 */
	public function addObject($object,$cdata=false,$encode=true) {
		$this->objectToNode($object,$this->root,$cdata,$encode);
	}

	/**
	 * Merges an object to the XML tree.
	 *
	 * $object parameter can also be an array but array keys must be strings.
	 *
	 * @access  public
	 * @param   object $object XMLVars object or array
	 * @param   boolean $cdata Defines if we create CDATA section to XMLtree
	 * @param   boolean $encode True/False if we want to run Common_Format::encode()
	 */
	public function mergeObject($object,$cdata=false,$encode=true) {
		
	}
	/**
	* Adds XML file to kernel's XML tree by Xpath.
	* For example if you have <foo><contentstuff>...</contentstuff></foo> use
	* addXML(location,"foo")
	*
	* @param $file Target file
	* @param $path Target Path in XML (xpath)
	* @return $retval Object
	*/
	public function addXML($file,$path) {
		if(!isset($file)) {
			throw new Exception("Target file needed");
		}
		if(!isset($path)) { 
			throw new Exception("Target XPath needed");
		}
		$dom=new DomDocument;
		$this->xml=file_get_contents($file);
		$dom->loadXML($this->xml);
		$this->xml=$dom;
		$this->xpath=new DOMXPath($this->xml);	
		$retval=$this->nodeToObject($path);
		$this->addObject($retval);
		return $retval;
	}	
	
	/**
	* Adds XML file to kernel's XML tree by Xpath.
	* For example if you have <foo><contentstuff>...</contentstuff></foo> use
	* addXML(location,"foo")
	*
	* @param $file Target file
	* @param $path Target Path in XML (xpath)
	* @return $retval Object
	*/	
	public function addXMLFromString($string,$path) {
		$dom=new DomDocument;
		$dom->loadXML($string);
		$this->xml=$dom;
		$this->xpath=new DOMXPath($this->xml);
		$retval=$this->nodeToObject($path);
		$this->addObject($retval);
		return $retval;
	}

	/**
	 * Parses object or array and converts it to XML.
	 *
	 * @todo   Validate $key (would it create too much overhead?)
	 * @access private
	 * @param  object $obj Object to parse. In theory arrays will work if keys are not integers.
	 * @param  object $top Toplevel DOM object
	 * @param  boolean $cdata Creates CDATASection if true
	 */
	protected function objectToNode($obj,$top,$cdata) {
		foreach($obj as $key=>$val) {
			if(is_array($val)) {
				foreach($val as $v) {
					if(is_object($v)) {
						$item=$this->dom->createElement($key);
						$this->objectToNode($v,$item,$cdata);
					} else {
						if($cdata) {
							$item=$this->dom->createElement($key);
							$cdata=$this->dom->createCDATASection(htmlspecialchars($val));
							$item->appendChild($cdata);
						} else {
							$item=$this->dom->createElement($key,htmlspecialchars($v));
						}
					}
					$top->appendChild($item);
				}
			} elseif(is_object($val)) {
				$item=$this->dom->createElement($key);
				$this->objectToNode($val,$item,$cdata);
				$top->appendChild($item);
			} else {
				if($cdata) {
					$item=$this->dom->createElement($key);
					$cdata=$this->dom->createCDATASection(htmlspecialchars($val));
					$item->appendChild($cdata);
				} else {
					$item=$this->dom->createElement($key,htmlspecialchars($val));
				}
				$top->appendChild($item);
			}
		}
	}
	
	/**
	 * Parses XML and converts it to object
	 *
	 * @access private
	 * @param  object $xpath XPath to XMLtree target, load with addXML()
	 */
	protected function nodeToObject($xpath) {
		$retval=new stdClass;
		$items=$this->xpath->query("{$xpath}");
		if(!is_object($items)) {
			return false;
		}
		if($items->length>1) {
			$retval=array();
			foreach($items as $item) {
				$count=count($retval)+1;
				array_push($retval,$this->nodeToObject("{$xpath}[{$count}]"));
			}
		} else {
			$nodelist=$this->xpath->query("{$xpath}/*");
			foreach($nodelist as $item) {
				if(isset($retval->{$item->nodeName})&&is_object($retval->{$item->nodeName})) {
					$retval->{$item->nodeName}=array(clone $retval->{$item->nodeName});
				}
				$tmp=$this->xpath->query("{$xpath}/{$item->nodeName}/*");
				if($tmp->length>0) {
					if(isset($retval->{$item->nodeName})) {
						$count=count($retval->{$item->nodeName})+1;
						array_push($retval->{$item->nodeName},$this->nodeToObject("{$xpath}/{$item->nodeName}[{$count}]"));
					} else {
						$retval->{$item->nodeName}=$this->nodeToObject("{$xpath}/{$item->nodeName}[1]");
					}
				} else {
					if(isset($retval->{$item->nodeName})) {
						array_push($retval->{$item->nodeName},$item->nodeValue);
					} else {
						$retval->{$item->nodeName}=$item->nodeValue;
					}
				}
			}
		}
		return $retval;
	}	

	/**
	 * self to string conversion.
	 *
	 * @access public
	 * @return string XML data as string
	 */
	public function __toString() {
		return (string)$this->parse();
	}

}
?>

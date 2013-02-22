<?php
/**
 * @package SamplePHPApi
 */
/**
 * class XMLHandler is a helper class to parse the XML feed data
 * @package SamplePHPApi
 */
class XMLHandler {
	/** @var Document */
	private $doc;

	/**
	 * @param String $url
	 * @return XMLHandler
	 */
	function __construct($url){
		$allowUrlFopenAvailable = ini_get('allow_url_fopen') == "1" || ini_get('allow_url_fopen') == "On";
		$cUrlAvailable = function_exists('curl_version');
		
		if (!$allowUrlFopenAvailable && !$cUrlAvailable)
		{
			$report = implode(", ", array(sprintf("allow_url_fopen is %s", ($allowUrlFopenAvailable ? "On" : "Off")), sprintf("cURL is %s", ($cUrlAvailable ? "enabled" : "disabled"))));
			throw new Exception(sprintf("No feed loading mechanism available - PHP reported %s", $report), "");
		}
		
		$this->doc = new DOMDocument();
		
		if ($allowUrlFopenAvailable)
		{
			if(!@$this->doc->load($url)) throw new XMLLoadException($url);
		}
		else if ($cUrlAvailable)
		{
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			
			$xml = curl_exec($ch);
			curl_close($ch);
			
			if (!@$this->doc->loadXML($xml)) throw new XMLLoadException($url);
		}
	}

	/**
	 * @param String $element
	 * @return String
	 */
	function getValue($element){
		$result = $this->doc->getElementsByTagName($element);
		if($result->length != null) return $this->doc->getElementsByTagName($element)->item(0)->nodeValue;
		else return null;
	}

	/**
	 * @param String $element
	 * @return String
	 */
	function getHrefValue($element){
		return $this->doc->getElementsByTagName($element)->item(0)->getAttribute('href');
	}

	/**
	 * @param String $element
	 * @param String $attribute
	 * @return String
	 */
	function getAttributeValue($element, $attribute){
		return $this->doc->getElementsByTagName($element)->item(0)->getAttribute($attribute);
	}

	/**
	 * @param String $element
	 * @return DOMNodeList
	 */
	function getNodes($element){
		return $this->doc->getElementsByTagName($element);
	}

	/**
	 * @param String $element
	 * @return String
	 */
	public static function getSetting($element){
		$xh = new XMLHandler("../Classes/settings.xml");
		return $xh->getValue($element);
	}
}

/**
 * Custom Exception XMLException
 * @package SamplePHPApi
 */
class XMLException extends Exception{}

/**
 * Custom Exception XMLLoadException thrown if an XML source file is not found
 * @package SamplePHPApi
 */
class XMLLoadException extends XMLException{
	function __construct($message, $code=""){
		$this->message = "Could not load URL: " . $message;
	}
}

/**
 * Custom Exception XMLNodeException thrown if a required XML element is not found
 * @package SamplePHPApi
 */
class XMLNodeException extends XMLException{
	function __construct($message, $code=""){
		$this->message = "Could not find XMLNode: " . $message;
	}
}
?>
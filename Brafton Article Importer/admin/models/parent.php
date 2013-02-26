<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modellist');
ini_set('max_execution_time', 300);
include_once 'ApiClientLibrary/ApiHandler.php';

class BraftonArticlesModelParent extends JModelList
{
	protected $feed;
	protected $options;
	protected $loadingMechanism;
	
	function __construct()
	{
		parent::__construct();
		
		JLog::addLogger(array('text_file' => 'com_braftonarticles.log.php'), JLog::ALL, 'com_braftonarticles');
		
		$allowUrlFopenAvailable = ini_get('allow_url_fopen') == "1" || ini_get('allow_url_fopen') == "On";
		$cUrlAvailable = function_exists('curl_version');
		
		if (!$allowUrlFopenAvailable && !$cUrlAvailable)
		{
			$report = implode(", ", array(sprintf("allow_url_fopen is %s", ($allowUrlFopenAvailable ? "On" : "Off")), sprintf("cURL is %s", ($cUrlAvailable ? "enabled" : "disabled"))));
			throw new Exception(sprintf("No feed loading mechanism available - PHP reported %s", $report), "");
		}
		
		// prioritize cURL over allow_url_fopen
		if ($cUrlAvailable)
			$this->loadingMechanism = "cURL";
		else if ($allowUrlFopenAvailable)
			$this->loadingMechanism = "allow_url_fopen";
		
		JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles'.DS.'tables');
		$this->options = $this->getTable('braftonoptions');
		
		// Load the API Key from the options
		$this->options->load('api-key');
		$API_Key = $this->options->value;
		
		// Load the base URL from the options
		$this->options->load('base-url');
		$API_BaseURL = $this->options->value;
		
		// Get a new feed handler
		$this->feed = new ApiHandler($API_Key, $API_BaseURL);
	}

}
?>
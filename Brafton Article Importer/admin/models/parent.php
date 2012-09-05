<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
// import the Joomla modellist library
jimport('joomla.application.component.modellist');
ini_set('max_execution_time', 300);
include_once 'ApiClientLibrary/ApiHandler.php';

// Acts as sort of a parent class to the article/category models, setting defaults n stuff.
class BraftonArticlesModelParent extends JModelList
{
	// Variable for feed
	protected $feed;
	protected $options;
	
	/*
	*	Default constructor - Sets the feed handler from the options
	*	PRE: N/A
	*	POST: No return - $feed is set as an ApiHandler
	*/
	function __construct() {
	
		// Cannot seem to call JModel::getTable() from the constructor without this line
		// even though you can call it without the include further down.  Possible Joomla bug?
		// EXPLORE FURTHER
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

		parent::__construct();
	} // end constructor

} // end class
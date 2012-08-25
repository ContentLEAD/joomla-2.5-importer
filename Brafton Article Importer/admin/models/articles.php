<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the Joomla modellist library
jimport('joomla.application.component.modellist');
ini_set('max_execution_time', 300);

include_once 'ApiClientLibrary/ApiHandler.php';

class BraftonArticlesModelArticles extends JModelList
{

	// Variable for feed
	protected $feed;
	
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
		$options = $this->getTable('braftonoptions');
		
		// Load the API Key from the options
		$options->load('api-key');
		$API_Key = $options->value;
		
		// Load the base URL from the options
		$options->load('base-url');
		$API_BaseURL = $options->value;
		
		// Get a new feed handler
		$this->feed = new ApiHandler($API_Key, $API_BaseURL);
		
		parent::__construct();
	} // end constructor
	
	public function getArticles() {
	
		$newsList = $this->feed->getNewsHTML(); //return an array of your latest news items with HTML encoding text. Note this is still raw data.
		
		foreach ($newsList as $article) {
			$row = $this->getTable('content');
			/*$brafton_id = $article->getId();
			$row_array = $this->initArray($brafton_id);*/
			$post_array['id'] = null;
			/*$test = $article->getCategories();
			$test = $test[0]->getID();
			*/
			$post_array['title'] = $article->getTitle();
			$post_array['fulltext'] = $article->getText();
			$row->save($post_array);
		}
	} // end getArticles
	
	/*
	*  Initializes array with default values
	*  A form of fault tolerance - if an article doesn't have
	*  a certain attribute on, it will default.
	*/
	private function initArray($brafton_id) {
	
		$row_array['id'] = null;
		$row_array['title'] = 'Article #'.$brafton_id;
		$row_array['alias'] = 'article-' . $brafton_id;
		$row_array['introtext'] = null;
		$row_array['fulltext'] = null;
		$state = 1;		
		$sectionid = 0;
		$mask = null;
		$catid = 2;
		$created = null;
		$created_by = null;
		$created_by_alias = null;
		$modified = null;
		$checked_out = null;
		$checked_out_time = null;
		$published_up = null;
		$published_down = null;
		$images = null;
		$urls = null;
		$attribs = null;
		$version = null;
		$parentid = null;
		$ordering = null;
		$metakey = null;
		$metadesc = null;
		$access = null;
		$hits = null;
		$featured = null;
		$language = null;
		$xreference = null;
		
		return $row_array;
	} // end initArray
} // end class
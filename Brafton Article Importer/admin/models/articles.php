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
	
	public function getArticles() {
	
		$newsList = $this->feed->getNewsHTML(); //return an array of your latest news items with HTML encoding text. Note this is still raw data.
		
		foreach ($newsList as $article) {
		
			$contentRow = $this->getTable('content');
			$brContentRow = $this->getTable('braftoncontent');
			$brCategoryRow = $this->getTable('braftoncategories');
			if(!$this->article_exists($article, $brContentRow)) {
				$articleData['id'] = null;	// primary key, must be null to auto increment
				$articleData['title'] = $article->getHeadline();	// Title of the article
				$articleData['alias'] = str_replace("'", "", str_replace(" ", "-", strtolower($article->getHeadline())));	// the alias is the title lowercased and spaces replaces with hyphens
				$articleData['fulltext'] = $article->getText();	// content of the article
				$articleData['state'] = 1; 	// automatically published, will make an option later
				$articleData['created'] = $article->getLastModifiedDate();	// modified date because this gets changed when the article is approved, so it'll actually post on the date that it's approved 
				$articleData['publish_up'] = $articleData['created'];	// Same as created date, quicker to reference the variable
				
				// Grab the author from the options table
				$this->options->load('author');
				$articleData['created_by'] = $this->options->value;
				
				// Logic for setting the category
				/*****************************************************************************************************/
				$categories = $article->getCategories();	// Get the list of categories from the XML
				$category = $categories[0];	// since Joomla can only hold one category, just grab the first one
				$keys['brafton_cat_id'] = $category->getId();	// Set the keys to load the appropriate row
				$brCategoryRow->load($keys);	// Load up the row
				$articleData['catid'] = $brCategoryRow->cat_id;		// Set the category id according to the row it found
				/*****************************************************************************************************/
				
				$articleData['language'] = '*';
				
				// save it!
				$contentRow->save($articleData);
				
				// Then associate the brafton categories with the ones inserted
				// Using the JTable class, cat_id can't be the primary key.  When trying to save(), it detects it as the primary
				// and it assumes you're updating the row with that key, instead of adding a row.  A blank primary key is needed to insert.
				$brContentData['id'] = null;
				// Since $categoryRow now contains the data from the last insert, we can use this id to our advantage
				$brContentData['content_id'] = $contentRow->id;
				$brContentData['brafton_content_id'] = (int) $article->getId();
				$brContentRow->save($brContentData);
			} // end if article exists
		}
	} // end getArticles
	
	private function article_exists($article, $brContentRow) {
		
		$brContentID = $article->getId();
		$keys['brafton_content_id'] = $brContentID;
		$brContentRow->load($keys);
		// If the row returns a key and not null, we know it exists.  Otherwise, it doesn't so it's safe to add
		if(!empty($brContentRow->brafton_content_id))
			return true;
		else
			return false;
	}
} // end class
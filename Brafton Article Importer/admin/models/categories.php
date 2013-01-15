<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once (JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles'.DS.'models'.DS.'parent.php');
require_once (JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles'.DS.'tables'.DS.'braftonoptions.php');
require_once (JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles'.DS.'tables'.DS.'braftoncategories.php');
jimport('joomla.database.table');
jimport('joomla.log.log');

class BraftonArticlesModelCategories extends JModelList
{
	// Variable for feed
	protected $feed;
	
	private $_braftonOptions;
	
	/*
	*	Default constructor - Sets the feed handler from the options
	*	PRE: N/A
	*	POST: No return - $feed is set as an ApiHandler
	*/
	function __construct()
	{
		parent::__construct();
		
		$this->_braftonOptions = JTable::getInstance('BraftonOptions', 'Table');
		
		// Load the API Key from the options
		$this->_braftonOptions->load('api-key');
		$API_Key = $this->_braftonOptions->value;
		
		// Load the base URL from the options
		$this->_braftonOptions->load('base-url');
		$API_BaseURL = $this->_braftonOptions->value;
		
		// Get a new feed handler
		$this->feed = new ApiHandler($API_Key, $API_BaseURL);
		
		JLog::addLogger(array());
	}
	
	// getCategories gets the categories from the XML feed and set it in the database.
	public function getCategories()
	{
		$categoryList = $this->feed->getCategoryDefinitions();
		
		foreach ($categoryList as $category)
		{
			$categoryRow = JTable::getInstance('Category');
			$brCategoryRow = JTable::getInstance('BraftonCategories', 'Table');
			
			if (!$this->category_exists($category, $brCategoryRow))
			{
				$categoryData = array(
					'title' =>			$category->getName(),
					'alias' =>			strtolower($category->getName()), /* check() handles slugification */
					'extension' =>		'com_content',
					'published' =>		1,
					'language' =>		'*',
					'level' =>			1,
					'parent_id' =>		1,
					'params' =>			'{"target":"","image":""}',
					'metadata' =>		'{"page_title":"","author":"","robots":""}',
					'access' =>			1
				);
				
				$categoryRow->bind($categoryData);
				$categoryRow->setLocation(1, 'last-child'); /* sets up the category in the tree */
				
				if (!$categoryRow->check() || !$categoryRow->store(true))
					JLog::add(sprintf('Unable to add category %s - %s', $category->getName(), $categoryRow->getError()), JLog::ERROR);
				else
					$categoryRow->rebuildPath($categoryRow->id);
			}
		}
	}
	
	/*
	*	$category - a NewsCategory item
	*	$row - connection to brafton_categories table
	*/
	private function category_exists($category, $brCategoryRow)
	{
		$jcatid = 0;
		$db = $brCategoryRow->getDbo();
		
		$q = $db->getQuery(true);
		$q->select('cat_id')->from('#__brafton_categories')->where('brafton_cat_id=' . $db->quote($category->getId()));
		
		$db->setQuery($q);
		
		if (!$db->loadRow())
			return false;
		return true;
	}
}
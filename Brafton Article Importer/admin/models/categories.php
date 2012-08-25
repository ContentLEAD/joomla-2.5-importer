<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
// import the Joomla modellist library
jimport('joomla.application.component.modellist');
/**
 * BraftonArticles Model
 */
class BraftonArticlesModelCategories extends JModelList
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
	
	public function getCategories() {
	
		$categoryList = $this->feed->getCategoryDefinitions();
		
		foreach ($categoryList as $category) {
			
			// Open up the tables for saving/loading etc.
			$categoryRow = $this->getTable('categories');
			$brCategoryRow = $this->getTable('braftoncategories');
			
			// If the category exists, we don't want it in there again!
			if(!$this->category_exists($category, $brCategoryRow)) {
				
				// First set the category in...
				$categoryData['id'] = null;
				$categoryData['title'] = $category->getName();
				$categoryRow->save($categoryData);
				
				// Then associate the brafton categories with the ones inserted
				// Using the JTable class, cat_id can't be the primary key.  When trying to save(), it detects it as the primary
				// and it assumes you're updating the row with that key, instead of adding a row.  A blank primary key is needed to insert.
				$brCategoryData['id'] = null;
				// Since $categoryRow now contains the data from the last insert, we can use this id to our advantage
				$brCategoryData['cat_id'] = $categoryRow->id;
				$brCategoryData['brafton_cat_id'] = (int) $category->getId();
				$brCategoryRow->save($brCategoryData);
			}
		}
	}
	
	/*
	*	$category - a NewsCategory item
	*	$row - connection to brafton_categories table
	*/
	private function category_exists($category, $row) {

		$brCategoryID = $category->getId();
		$keys['brafton_cat_id'] = $brCategoryID;
		$row->load($keys);
	}
}

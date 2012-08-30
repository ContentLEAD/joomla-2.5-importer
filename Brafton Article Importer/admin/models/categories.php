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
	
	// The client may have to run "Rebuild Categories" after the fact.  Doing this sets the lft and rgt settings
	// Using the "Rebuild Categories" function is probably a LOT safer then me fumbling around trying to fix it myself
	
	// getCategories gets the categories from the XML feed and set it in the database.
	public function getCategories() {
	
		$categoryList = $this->feed->getCategoryDefinitions();
		
		foreach ($categoryList as $category) {
			
			// Open up the tables for saving/loading etc.
			$categoryRow = $this->getTable('categories');
			$brCategoryRow = $this->getTable('braftoncategories');
			
			// If the category exists, we don't want it in there again!
			if(!$this->category_exists($category, $brCategoryRow)) {
				
				// First set the category in...
				// There's a lot of Joomla specific stuff here.
				// There may be a way to establish this stuff using the JTable instance
				$categoryData['id'] = null;		// primary key, must be null to auto_increment
				$categoryData['title'] = $category->getName();	// the title, aka category name
				$categoryData['alias'] = str_replace(" ", "-", strtolower($category->getName()));	// the alias is the title lowercased and spaces replaces with hyphens
				$categoryData['path'] = $categoryData['alias']; // path is the same as the alias
				$categoryData['parent_id'] = 1; 	// default to root parent
				$categoryData['extension'] = 'com_content'; // yes this is correct, the articles are being pushed into com_content
				$categoryData['published'] = 1; // auto published category, may make an option at a later date
				$categoryData['language'] = '*';
				$categoryRow->save($categoryData);
				
				// Then associate the brafton categories with the ones inserted
				// Using the JTable class, cat_id can't be the primary key.  When trying to save(), it detects it as the primary
				// and it assumes you're updating the row with that key, instead of adding a row.  A blank primary key is needed to insert.
				$brCategoryData['id'] = null;
				// Since $categoryRow now contains the data from the last insert, we can use this id to our advantage
				$brCategoryData['cat_id'] = $categoryRow->id;
				$brCategoryData['brafton_cat_id'] = (int) $category->getId();
				$brCategoryRow->save($brCategoryData);
			} // end if category exists
		} //end foreach
	} //end getCategories
	
	/*
	*	$category - a NewsCategory item
	*	$row - connection to brafton_categories table
	*/
	private function category_exists($category, $brCategoryRow) {

		$brCategoryID = $category->getId();
		$keys['brafton_cat_id'] = $brCategoryID;
		$brCategoryRow->load($keys);
		// If the row returns a key and not null, we know it exists.  Otherwise, it doesn't so it's safe to add
		if(!empty($brCategoryRow->brafton_cat_id))
			return true;
		else
			return false;
	} // end category_exists
} // end class
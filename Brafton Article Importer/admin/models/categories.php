<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once (JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles'.DS.'models'.DS.'parent.php');
require_once (JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles'.DS.'tables'.DS.'braftonoptions.php');
require_once (JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles'.DS.'tables'.DS.'braftoncategories.php');
jimport('joomla.database.table');
jimport('joomla.log.log');

class BraftonArticlesModelCategories extends BraftonArticlesModelParent
{	
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
					'title' =>			trim($category->getName()),
					'alias' =>			strtolower(trim($category->getName())), /* check() handles slugification */
					'extension' =>		'com_content',
					'published' =>		1,
					'language' =>		'*',
					'level' =>			1,
					'parent_id' =>		1,
					'params' =>			'{"category_layout":"","image":""}',
					'metadata' =>		'{"author":"","robots":"noindex, follow"}',
					'access' =>			1
				);
				
				$categoryRow->bind($categoryData);
				$categoryRow->setLocation(1, 'last-child'); /* sets up the category in the tree */
				
				if (!$categoryRow->check() || !$categoryRow->store(true))
					JLog::add(sprintf('Unable to add category %s - %s', $category->getName(), $categoryRow->getError()), JLog::ERROR, 'com_braftonarticles');
				else
					$categoryRow->rebuildPath($categoryRow->id);
				
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
?>
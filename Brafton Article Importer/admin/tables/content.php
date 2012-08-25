<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');
 
/**
 * Hello Table class
 */
class TableContent extends JTable
{
	var $id = null;
	var $asset_id = null;
	var $title = null;
	var $alias = null;
	var $title_alias = null;
	var $introtext = null;
	var $fulltext = null;
	var $state = null;
	var $sectionid = null;
	var $mask = null;
	var $catid = null;
	var $created = null;
	var $created_by = null;
	var $created_by_alias = null;
	var $modified = null;
	var $checked_out = null;
	var $checked_out_time = null;
	var $published_up = null;
	var $published_down = null;
	var $images = null;
	var $urls = null;
	var $attribs = null;
	var $version = null;
	var $parentid = null;
	var $ordering = null;
	var $metakey = null;
	var $metadesc = null;
	var $access = null;
	var $hits = null;
	var $featured = null;
	var $language = null;
	var $xreference = null;
	
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function __construct(&$db) 
	{
		parent::__construct('#__content', 'id', $db);
	}
		
	/**
	 * Method to compute the default name of the asset.
	 * The default name is in the form `table_name.id`
	 * where id is the value of the primary key of the table.
	 *
	 * @return	string
	 * @since	2.5
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;
		return 'com_content.article.'.(int) $this->$k;
	}
 
	/**
	 * Method to return the title to use for the asset table.
	 *
	 * @return	string
	 * @since	2.5
	 */
	protected function _getAssetTitle()
	{
		return $this->title;
	}
 
	/**
	 * Method to get the asset-parent-id of the item
	 *
	 * @return	int
	 */
	protected function _getAssetParentId()
	{
		// We will retrieve the parent-asset from the Asset-table
		$assetParent = JTable::getInstance('Asset');
		// Default: if no asset-parent can be found we take the global asset
		$assetParentId = $assetParent->getRootId();
 
		// Find the parent-asset
		if (($this->catid)&& !empty($this->catid))
		{
			// The item has a category as asset-parent
			$assetParent->loadByName('com_content.category.' . (int) $this->id);
		}
		else
		{
			// The item has the component as asset-parent
			$assetParent->loadByName('com_content');
		}
 
		// Return the found asset-parent-id
		if ($assetParent->id)
		{
			$assetParentId=$assetParent->id;
		}
		return $assetParentId;
	}
}

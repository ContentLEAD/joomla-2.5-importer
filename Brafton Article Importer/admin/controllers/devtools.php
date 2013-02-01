<?php
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');
jimport('joomla.log.log');

/*
	Controller for developer tools - stuff to fix the guts if something goes wrong.
	Don't put regular import routines in here.
*/
class BraftonArticlesControllerDevTools extends JControllerAdmin
{
	private $_braftonOptions;
	private $_feed;
	private $_db;
	
	function __construct()
	{
		parent::__construct();
		
		JLoader::import('models.ApiClientLibrary.ApiHandler', JPATH_COMPONENT_ADMINISTRATOR);
		JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles'.DS.'tables');
		$this->_braftonOptions = JTable::getInstance('BraftonOptions', 'Table');
		
		$this->_braftonOptions->load('api-key');
		$API_Key = $this->_braftonOptions->value;
		
		$this->_braftonOptions->load('base-url');
		$API_BaseURL = $this->_braftonOptions->value;
		
		$this->_feed = new ApiHandler($API_Key, $API_BaseURL);
		$this->_db = $this->_braftonOptions->getDbo();
		
		JLog::addLogger(array('text_file' => 'com_braftonarticles.log.php'), JLog::ALL, 'com_braftonarticles');
	}
	
	function sync_categories()
	{
		JLog::add('Starting category sync.', JLog::DEBUG, 'com_braftonarticles');
		
		$msg = '';
		$syncCount = 0;
		$feedCats = $this->_feed->getCategoryDefinitions();
		
		// we want to start fresh! - lingering rows can mess everything up
		$this->_db->setQuery('DELETE FROM `#__brafton_categories`');
		if ($this->_db->execute())
			JLog::add(sprintf('Cleared importer category list, %d rows affected.', $this->_db->getAffectedRows()), JLog::INFO, 'com_braftonarticles');
		else
		{
			JLog::add(sprintf('Could not clear importer category list: %s', $this->_db->getErrorMsg()), JLog::ERROR, 'com_braftonarticles');
			$this->redirect('index.php?option=com_braftonarticles', 'Could not sync categories.', 'error');
		}
		
		foreach ($feedCats as $fc)
		{
			$q = $this->_db->getQuery(true);
			$q->select('id')->from('#__categories')->where(array(sprintf('`title` = %s', $q->q($fc->getName())), '`extension` = "com_content"'))->order('id ASC');
			$this->_db->setQuery($q, 0, 1);
			
			$jCatId = $this->_db->loadResult();
			// if there's no suitable category, ignore it. it'll be imported on the next run of the importer.
			if (!$jCatId)
				continue;
			
			$bCats = JTable::getInstance('BraftonCategories', 'Table');
			
			if (!$bCats->save(array('brafton_cat_id' => $fc->getId(), 'cat_id' => $jCatId)))
				JLog::add(sprintf('Unable to add category %s (%d) - %s', $fc->getName(), $fc->getId(), $bCats->getError()), JLog::ERROR);
			else
				$syncCount++;
		}
		
		JLog::add(sprintf('Finishing category sync. Synced %d categories.', $syncCount), JLog::DEBUG, 'com_braftonarticles');
		
		$msg = 'Categories have been synchronized.';
		$msgType = 'message';
		if ($syncCount == 0)
		{
			$msg = 'Synchronization complete, but zero categories synced.';
			$msgType = 'notice';
		}
		
		$this->setRedirect('index.php?option=com_braftonarticles', $msg);
	}
}
?>
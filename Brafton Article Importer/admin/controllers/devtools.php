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
	
	function rebuild_content_listing()
	{
		JLog::add('Rebuilding content listing.', JLog::DEBUG, 'com_braftonarticles');
		
		$tableStructures = $this->getTableStructures();
		
		if ($tableStructures === false)
		{
			JLog::add('Error: Content listing tables not found.', JLog::ERROR, 'com_braftonarticles');
			$this->setRedirect('index.php?option=com_braftonarticles', 'Content listing was not rebuilt: tables not found.', 'error');
			return;
		}
		
		$content = $this->getContentListing($tableStructures);
		
		if ($this->resetContentListing())
			$this->saveContentListing($content);
		
		JLog::add('Content listing rebuilt.', JLog::INFO, 'com_braftonarticles');
		$this->setRedirect('index.php?option=com_braftonarticles', 'Content listing rebuilt.', 'message');
	}
	
	private function getTableStructures()
	{
		$tableStructures = array();
		$db = JFactory::getDbo();
		$prefix = $db->getPrefix();
		$allTables = $db->getTableList();
		
		if (in_array($prefix . 'brafton', $allTables))
			$tableStructures[] = '1.5';
		
		if (in_array($prefix . 'brafton_content', $allTables))
			$tableStructures[] = '2.5';
		
		if (empty($tableStructures))
			return false;
		
		return $tableStructures;
	}
	
	private function saveContentListing($content)
	{
		if (empty($content))
			return;
		
		$count = 0;
		$db = JFactory::getDbo();
		
		foreach ($content as $row)
		{
			$q = $db->getQuery(true);
			$q->insert('#__brafton_content')->columns('brafton_content_id', 'content_id');
			$q->values($row[0] . ', ' . $row[1]);
			
			$db->setQuery($q);
			if ($db->query())
				$count++;
			else
				JLog::add(sprintf('Notice: Could not migrate row (content ID: %d, Brafton ID: %d): [%d] %s', $row[1], $row[0], $db->getErrorNum(), $db->getErrorMsg()), JLog::NOTICE, 'com_braftonarticles');
		}
	}
	
	private function getContentListing($tableStructures)
	{
		$content = array();
		$db = JFactory::getDbo();
		
		foreach ($tableStructures as $struc)
		{
			$tableName = '';
			$contentIdColumn = '';
			$brafIdColumn = '';
			
			switch ($struc)
			{
				case '1.5':
					$tableName = '#__brafton';
					$contentIdColumn = 'id';
					$brafIdColumn = 'brafton_id';
					break;
				
				case '2.5':
					$tableName = '#__brafton_content';
					$contentIdColumn = 'content_id';
					$brafIdColumn = 'brafton_content_id';
					break;
				
				default:
					continue;
			}
			
			$q = $db->getQuery(true);
			$q->select($q->qn($brafIdColumn))->select($q->qn($contentIdColumn));
			$q->from($tableName);
			$db->setQuery($q);
			
			$rows = $db->loadRowList();
			foreach ($rows as $brafId => $contentId)
				$content[$brafId] = $contentId;
		}
		
		return $content;
	}
	
	private function resetContentListing()
	{
		JLog::add('Resetting content listing tables.', JLog::DEBUG, 'com_braftonarticles');
		
		$db = JFactory::getDbo();
		
		$installSql = JPATH_ADMINISTRATOR . '/components/com_braftonarticles/sql/install.mysql.utf8.sql';
		$sql = JFile::read($installSql);
		$msg = '';
		$msgType = 'message';
		$result = false;
		
		if ($sql)
		{
			$db->transactionStart();
			$db->dropTable('#__brafton');
			$db->dropTable('#__brafton_content');
			$db->setQuery($sql);
			if ($db->queryBatch())
			{
				$db->transactionCommit();
				
				$result = true;
			}
			else
			{
				$msg = 'Content listing could not be purged.';
				$msgType = 'error';
				JLog::add(sprintf('Error: Could not execute SQL: [%d] %s', $db->getErrorNum(), $db->getErrorMsg()), JLog::ERROR, 'com_braftonarticles');
				$db->transactionRollback();
				
				$result = false;
			}
		}
		else
		{
			$msg = 'Could not load installation SQL.';
			$msgType = 'error';
			JLog::add('Error: Could not load installation SQL.', JLog::ERROR, 'com_braftonarticles');
			
			$result = false;
		}
		
		if (!$result)
			$this->setRedirect('index.php?option=com_braftonarticles', $msg, $msgType);
		return $result;
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
				JLog::add(sprintf('Unable to add category %s (%d) - %s', $fc->getName(), $fc->getId(), $bCats->getError()), JLog::ERROR, 'com_braftonarticles');
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
		
		$this->setRedirect('index.php?option=com_braftonarticles', $msg, $msgType);
	}
}
?>
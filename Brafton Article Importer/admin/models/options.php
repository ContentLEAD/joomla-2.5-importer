<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
// import the Joomla modellist library
jimport('joomla.application.component.modellist');
jimport('joomla.error.error');
/**
 * BraftonArticlesOptions Model
 */
class BraftonArticlesModelOptions extends JModelList
{
	protected $optionsTable;
	protected $authorTable;
	
	function __construct() {
		JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles'.DS.'tables');
		$this->optionsTable = $this->getTable('braftonoptions');		
		parent::__construct();
	}
	
	// This sets the options in the DB
	// Called from the options sub-controller
	function setOptions() {
		
		// Set all needed variables
		$API_pattern = "[a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{12}";
		$baseURL_pattern = "^(http:\/\/)?api\.[^.]*\.(com|com\.au|co\.uk)\/";
		$options = JRequest::get('post');
		
		if(!preg_match('/'.$API_pattern.'/', $options['api-key'], $apiKey)) {
			JError::raiseWarning(100, 'There was a problem registering your base URL.  Please double check and try again.');
			return;
		}
		if(!preg_match('/'.$baseURL_pattern.'/', $options['api-key'], $baseURL)){
			JError::raiseWarning(100, 'There was a problem registering your API key.  Please double check and try again.');
			return;
		}

		/* Push in the key, bust not before some checking... */
		// Scrub the key
		$apiKey[0] = trim(stripslashes($apiKey[0]), '/');
		
		// Lookin good, let's push this in
		$APIKeyData['option'] = "api-key";
		$APIKeyData['value'] = $apiKey[0];
		if(!$this->optionsTable->save($APIKeyData)) {
			JError::raiseError(500, $this->optionsTable->getError());
			return;
		}
		
		/* Push in the base url */
		$baseURLData['option'] = "base-url";
		$baseURLData['value'] = $baseURL[0];
		if(!$this->optionsTable->save($baseURLData)) {
			JError::raiseError(500, $this->optionsTable->getError());
			return;
		}
		
		$authorData['option'] = "author";
		$authorData['value'] = $options['author'];
		if(!$this->optionsTable->save($authorData)) {
			JError::raiseError(500, $this->optionsTable->getError());
			return;
		}
		
		$importOrderData['option'] = 'import-order';
		$importOrderData['value'] = $options['import-order'];
		if(!$this->optionsTable->save($importOrderData)) {
			JError::raiseError(500, $this->optionsTable->getError());
			return;
		}
		
		$publishedStateData['option'] = 'published-state';
		$publishedStateData['value'] = $options['published-state'];
		if(!$this->optionsTable->save($publishedStateData)) {
			JError::raiseError(500, $this->optionsTable->getError());
			return;
		}
		
		JFactory::getApplication()->enqueueMessage('Your options have successfully been saved.  Please note that your articles will not import until you have activated the <a href="index.php?option=com_plugins">bundled cron plugin</a>.');
	}
	
	/* getAPIKey()
	 * Pre - N/A
	 * Post - returns API Key, string
	 */ 
	function getAPIKey() {
		$this->optionsTable->load('api-key');
		return $this->optionsTable->value;
	}
	
	/* getBaseURL()
	 * Pre - N/A
	 * Post - returns base URL, string
	 */
	function getBaseURL () {
		$this->optionsTable->load('base-url');
		return $this->optionsTable->value;
	}
	
	/* getAuthor()
	 * Pre - N/A
	 * Post - returns author, string
	 */
	function getAuthor() {
		$this->optionsTable->load('author');
		return $this->optionsTable->value;
	}
	
	function getAuthorList() {
		$db = JFactory::getDBO();
		$query = "SELECT name, id FROM #__users";
		$db->setQuery($query);
		$authors = $db->loadObjectList();
		return $authors;
	}
	
	function getImportOrder() {
		$this->optionsTable->load('import-order');
		return $this->optionsTable->value;
	}
	
	function getPublishedState() {
		$this->optionsTable->load('published-state');
		return $this->optionsTable->value;
	}
	
} // end class
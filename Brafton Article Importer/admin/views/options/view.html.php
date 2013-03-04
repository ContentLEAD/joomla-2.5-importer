<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
 
/**
 * Options View
 */
class BraftonArticlesViewOptions extends JView
{
	protected $api_key;
	protected $base_url;
	protected $importOrder;
	protected $publishedState;
	protected $updateArticles;
	
	function display($tpl = null)
	{
		$toolbar = JToolBar::getInstance();
		JHtml::stylesheet('com_braftonarticles/css/admin/style.css', 'media/');
		
		JToolBarHelper::title('Brafton Article Importer','logo');
		JToolBarHelper::apply('options.apply');
		JToolBarHelper::cancel('options.cancel');
		JToolBarHelper::divider();
		$toolbar->appendButton('Confirm', 'This will build the importing category structure from scratch! Are you sure you want to do this?', 'refresh', 'Sync Categories', 'devtools.sync_categories', false);
		$toolbar->appendButton('Confirm', 'This will attempt to rebuild the listing of loaded Brafton content. This may have severe consequences and is irreversible! Are you sure you want to do this?', 'purge', 'Rebuild Content Listing', 'devtools.rebuild_content_listing', false);
		
		$this->api_key = $this->get('APIKey');
		$this->base_url = $this->get('BaseURL');
		$this->author = $this->get('Author');
		$this->authorList = $this->get('AuthorList');
		$this->importOrder = $this->get('ImportOrder');
		$this->publishedState = $this->get('PublishedState');
		$this->updateArticles = $this->get('UpdateArticles');
		
		parent::display($tpl);
	}
}
?>

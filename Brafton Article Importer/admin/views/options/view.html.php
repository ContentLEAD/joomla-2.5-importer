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
	
	function display($tpl = null) 
	{
		JToolBarHelper::title('Brafton Article Importer','logo');
		JToolBarHelper::apply('options.apply');
		JToolBarHelper::cancel('options.cancel');
		JHtml::stylesheet('com_braftonarticles/css/admin/style.css', 'media/');
		$this->api_key = $this->get('APIKey');
		$this->base_url = $this->get('BaseURL');
		$this->author = $this->get('Author');
		$this->authorList = $this->get('AuthorList');
		$this->importOrder = $this->get('ImportOrder');
		parent::display($tpl);
	}
}

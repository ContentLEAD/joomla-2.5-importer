<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
 
/**
 * BraftonArticles View
 */
class BraftonArticlesViewBraftonArticles extends JView
{
	/**
	 * BraftonArticles view display method
	 * @return void
	 */
	function display($tpl = null) 
	{	
		$this->get('Categories', 'Categories');
		$this->get('Articles');
		// Display the template
		parent::display($tpl);
	}
}

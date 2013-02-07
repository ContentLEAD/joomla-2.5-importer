<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla controller library
jimport('joomla.application.component.controller');
 
/**
 * General Controller of BraftonArticles component
 */
class BraftonArticlesController extends JController
{
	/**
	 * display task
	 *
	 * @return void
	 */
	function display($cachable = false, $urlparams = false) 
	{
		$jinput = JFactory::getApplication()->input;
		$viewName = $jinput->get('view', 'Options');
		$jinput->set('view', $viewName);
		
		parent::display($cachable);
	}
}
?>
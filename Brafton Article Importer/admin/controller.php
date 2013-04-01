<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');
jimport('joomla.version');

/**
 * General Controller of BraftonArticles component
 */
class BraftonArticlesController extends JController
{
	function display($cachable = false, $urlparams = false) 
	{
		$version = new JVersion();
		$joomlaVersion = $version->getShortVersion();
		
		if (version_compare($joomlaVersion, '2.5', '<'))
		{
			$view = JRequest::getVar('view', 'options');
			JRequest::setVar('view', $view);
		}
		else
		{
			$jinput = JFactory::getApplication()->input;
			$view = $jinput->get('view', 'Options');
			$jinput->set('view', $view);
		}
		
		parent::display($cachable);
	}
}
?>
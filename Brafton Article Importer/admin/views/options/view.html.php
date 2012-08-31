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
	function display($tpl = null) 
	{
		JToolBarHelper::title('Brafton Article Importer','logo');
		JToolBarHelper::apply('options.apply');
		JToolBarHelper::save();
		JToolBarHelper::cancel();
		JHtml::stylesheet('com_braftonarticles/css/admin/style.css', 'media/');
		parent::display($tpl);
	}
}

<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');
jimport('joomla.filesystem.file');

include_once JPATH_CONFIGURATION . '/configuration.php';

class BraftonArticlesViewLog extends JView
{
	protected $logContents;
	
	function display($tpl = null)
	{
		$toolbar = JToolBar::getInstance();
		JHtml::stylesheet('com_braftonarticles/css/admin/style.css', 'media/');
		JToolBarHelper::title('Brafton Article Importer','logo');
		
		$config = new JConfig();
		$logPath = rtrim($config->log_path, '/') . '/com_braftonarticles.log.php';
		if (JFile::exists($logPath))
			$this->logContents = JFile::read($logPath);
		else
		{
			$app = JFactory::getApplication();
			$app->enqueueMessage('Empty log file.');
		}
		
		parent::display($tpl);
	}
}
?>
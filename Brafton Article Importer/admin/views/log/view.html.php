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
		
		$toolbar->appendButton('Confirm', 'This will build the importing category structure from scratch! Are you sure you want to do this?', 'refresh', 'Sync Categories', 'devtools.sync_categories', false);
		
		$config = new JConfig();
		$logPath = $config->log_path . '/com_braftonarticles.log.php';
		$this->logContents = JFile::read($logPath);
		
		parent::display($tpl);
	}
}
?>
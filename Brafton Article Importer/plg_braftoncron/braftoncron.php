<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.plugin.plugin' );
jimport( 'joomla.html.parameter' );

class plgSystemBraftonCron extends JPlugin
{
	protected $interval	= 300;
	function plgSystemBraftonCron( &$subject, $params )
	{
		parent::__construct( $subject, $params );
		$this->plugin	=& JPluginHelper::getPlugin('system', 'braftoncron');
		$this->params	= new JParameter($this->plugin->params);
		$this->interval	= (int) ($this->params->get('interval', 5)*60);
		if ($this->interval < 300) { $this->interval = 300; }
	}

	function onAfterRoute()
	{
		$app = &JFactory::getApplication();

		if ($app->isSite()) {
			$now = &JFactory::getDate();
			$now = $now->toUnix();	

			if($last = $this->params->get('last_import')) {
				$diff = $now - $last;	
			} else {	
				$diff = $this->interval+1;
			}

			if ($diff > $this->interval) {
	
				require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles'.DS.'controllers'.DS.'cron.php');
				$config = array('base_path'=>JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles');
				$controller = new BraftonArticlesControllerCron($config);
				
				$controller->execute('loadCategories');
				$controller->execute('loadArticles');
				
				$db	= JFactory::getDbo();
				$this->params->set('last_import',$now);	
				$params = '';
				$params .= 'interval='.$this->params->get('interval',5)."\n";
				$params .= 'last_import='.$now."\n";
				$query = 	'UPDATE #__extensions'.
							' SET params='.$db->Quote($params).
							' WHERE element = '.$db->Quote('braftoncron').
							' AND folder = '.$db->Quote('system').
							' AND published >= 1';
				$db->setQuery($query);
				$db->query();
			} 
		} 
	} 
}
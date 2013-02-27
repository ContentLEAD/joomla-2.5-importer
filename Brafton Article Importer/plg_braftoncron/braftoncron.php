<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.plugin.plugin' );
jimport( 'joomla.html.parameter' );
jimport('joomla.log.log');

class plgSystemBraftonCron extends JPlugin
{
	protected $interval	= 300;
	function plgSystemBraftonCron( &$subject, $params )
	{
		parent::__construct( $subject, $params );
		
		JLog::addLogger(array('text_file' => 'com_braftonarticles.log.php'), JLog::ALL, 'com_braftonarticles');
		
		$this->plugin = JPluginHelper::getPlugin('system', 'braftoncron');
		$this->params->get('params');
		
		$this->interval	= ((int)$this->params->get('interval', 5)) * 60;
		if ($this->interval < 300)
			$this->interval = 300;
	}

	function onAfterRoute()
	{
		$app = JFactory::getApplication();

		if ($app->isSite()) {
			$now = JFactory::getDate();
			$now = $now->toUnix();	

			if($last = $this->params->get('last_import'))
				$diff = $now - $last;
			else
				$diff = $this->interval + 1;

			if ($diff > $this->interval)
			{
				// check for dependencies
				$needed = array('DOMDocument');
				$missing = array();
				foreach ($needed as $n)
					if (!class_exists($n))
						$missing []= $n;
				
				if (!empty($missing))
				{
					JLog::add(sprintf('Cannot trigger importer. Missing dependencies: %s.', implode(', ', $missing)), JLog::ERROR, 'com_braftonarticles');
					return;
				}
				
				require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles'.DS.'controllers'.DS.'cron.php');
				$config = array('base_path'=>JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles');
				$controller = new BraftonArticlesControllerCron($config);
				
				try
				{
					$controller->execute('loadCategories');
					$controller->execute('updateArticles');
					$controller->execute('loadArticles');
				}
				catch (Exception $ex)
				{
					JLog::add(sprintf('FATAL: Uncaught exception: %s. Stack Trace: ' . "\n" . '%s', $ex->getMessage(), $ex->getTraceAsString()), JLog::CRITICAL, 'com_braftonarticles');
				}
				
				$db	= JFactory::getDbo();
				$this->params->set('last_import',$now);	
				$params = '';
				$params .= 'interval='.$this->params->get('interval',5)."\n";
				$params .= 'last_import='.$now."\n";
				$query = 	'UPDATE #__extensions'.
							' SET params='.$db->Quote($params).
							' WHERE element = '.$db->Quote('braftoncron').
							' AND folder = '.$db->Quote('system').
							' AND enabled = 1';
				$db->setQuery($query);
				$db->query();
			} 
		} 
	} 
}
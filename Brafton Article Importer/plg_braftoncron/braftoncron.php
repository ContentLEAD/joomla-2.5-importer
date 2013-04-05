<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.plugin.plugin' );
jimport( 'joomla.html.parameter' );
jimport('joomla.log.log');
jimport('joomla.database.table');

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
	
	function onBeforeRender()
	{	
		$app = JFactory::getApplication();
		// we should be on the site, not in edit mode, and viewing a single article.
		if ($app->isAdmin() || JRequest::getCmd('task') == 'edit' || JRequest::getCmd('layout') == 'edit' || !(JRequest::getCmd('option') == 'com_content' && JRequest::getCmd('view') == 'article'))
			return;
		
		JTable::addIncludePath(JPATH_ROOT . '/libraries/joomla/database/table');
		$article = JTable::getInstance('content');
		$articleId = JRequest::getVar('id');
		$article->load($articleId);
		
		$tags = array('title', 'type', 'url', 'image');
		$og = $this->generateOpenGraphTags($tags, $article);
		
		$doc = JFactory::getDocument();
		foreach ($og as $property => $tag)
			if ($tag != null)
				$doc->addCustomTag($tag);
	}
	
	private function generateOpenGraphTags($tagList, $article = null, $prefix = 'og:')
	{
		if (!is_array($tagList) || empty($tagList) || $article == null)
			return array();
		
		$ogTags = array();
		
		foreach ($tagList as $t)
		{
			$tag = strtolower($t);
			
			switch ($tag)
			{
				case 'title':
					$ogTags[$t] = $this->createOpenGraphTag($prefix . $tag, $article->title);
					break;
				
				case 'type':
					$ogTags[$t] = $this->createOpenGraphTag($prefix . $tag, 'article');
					break;
				
				case 'url':
					$ogTags[$t] = $this->createOpenGraphTag($prefix . $tag, JURI::current());
					break;
				
				case 'image':
					$imageUrl = $this->findImageUrl($article);
					if ($imageUrl)
						$ogTags[$t] = $this->createOpenGraphTag($prefix . $tag, $imageUrl);
					else
						$ogTags[$t] = null;
					break;
				
				default:
					break;
			}
		}
		
		return $ogTags;
	}
	
	private function findImageUrl($article)
	{
		// for now, let's scrape the URL out of the image tag if they exist - custom fields later.
		$matches = array();
		$success = preg_match('/<img src="(.*?)"/', $article->fulltext, $matches);
		
		if (!$success)
			return false;
		$imageUrl = $matches[1];
		
		$juri = JURI::getInstance();
		$port = $juri->getPort();
		return $juri->getScheme() . '://' . $juri->getHost() . ($port != '80' ? $port : '') . $imageUrl;
	}
	
	private function findImageInText($text)
	{
	}
	
	private function createOpenGraphTag($property, $content)
	{
		return sprintf('<meta property="%s" content="%s" />', $property, htmlspecialchars($content));
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
					if ($this->updateArticlesEnabled())
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
	
	private function updateArticlesEnabled()
	{
		$db = JFactory::getDbo();
		$q = $db->getQuery(true);
		
		$q->select($q->qn('value'))->from('#__brafton_options')->where($q->qn('option') . ' = ' . $q->q('update-articles'));
		
		$db->setQuery($q);
		$result = $db->loadResult();
		
		return $result == 'On';
	}
}

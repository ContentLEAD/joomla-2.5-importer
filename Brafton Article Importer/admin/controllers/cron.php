<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controller library
jimport('joomla.application.component.controller');

class BraftonArticlesControllerCron extends JController
{
	function __construct( $config = array())
	{
		parent::__construct( $config );
	}

	function display($cachable = false, $urlparams = false) 
	{
		// set default view if not set
		JRequest::setVar('view', JRequest::getCmd('view','Options'));
		parent::display($cachable);
	}
	
	function loadCategories()
	{
		$model = $this->getModel('categories');
		if(!$model->getCategories()) {
			return false;
		} else {
			return true;
		}
	}
	
	function loadArticles()
	{
		$model = $this->getModel('articles');
		if(!$model->loadArticles()) {
			return false;
		} else {
			return true;
		}
	}
	
	function updateArticles()
	{
		$model = $this->getModel('articles');
		if(!$model->updateArticles()) {
			return false;
		} else {
			return true;
		}
	}
}
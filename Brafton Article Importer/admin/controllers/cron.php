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

	function display($cachable = false) 
	{
		// set default view if not set
		JRequest::setVar('view', JRequest::getCmd('view','Options'));
		parent::display($cachable);
	}

	/*
		loadArticles() - REQUIRED FOR BRAFTON ARTICLE IMPORTER
		This grabs the model code and starts the import of the articles
		NOTE: This is just the articles, for pictures see below.
		TODO: Error checking. possible redirect?
	*/
	function loadCategories()
	{
		$model = $this->getModel('categories');
		if(!$model->getCategories())) {
			return false;
		} else {
			return true;
		}
	}
	function loadArticles()
	{
		$model = $this->getModel('articles');
		if(!$model->getArticles()) {
			return false;
		} else {
			return true;
		}
	}

	function loadPictures()
	{
		$model = $this->getModel('braftonarticles');
		if(!$model->loadpics()) {
			return false;
		} else {
			return true;
		}
	}
}
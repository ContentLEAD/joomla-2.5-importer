<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import joomla controller library
jimport('joomla.application.component.controller');


/*
Load controllers here...
*/
// Get an instance of the controller prefixed by HelloWorld
$controller = JController::getInstance('BraftonArticles');

$jinput = JFactory::getApplication()->input;
$view = strtolower($jinput->get('view', 'options'));
JSubMenuHelper::addEntry('Settings', 'index.php?option=com_braftonarticles', $view == 'options');
JSubMenuHelper::addEntry('Log', 'index.php?option=com_braftonarticles&view=log', $view == 'log');
 
// Perform the Request task
$controller->execute(JRequest::getCmd('task'));
 
// Redirect if set by the controller
$controller->redirect();
?>
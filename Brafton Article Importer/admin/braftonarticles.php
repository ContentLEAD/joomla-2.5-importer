<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');
jimport('joomla.version');

$controller = JController::getInstance('BraftonArticles');

$version = new JVersion();
$joomlaVersion = $version->getShortVersion();
$view = '';
$task = '';

if (version_compare($joomlaVersion, '2.5', '<'))
{
	$view = strtolower(JRequest::getVar('view', 'options'));
	$task = JRequest::getCmd('task');
}
else
{
	$jinput = JFactory::getApplication()->input;
	$view = strtolower($jinput->get('view', 'options'));
	$task = $jinput->get('task');
}
JSubMenuHelper::addEntry('Settings', 'index.php?option=com_braftonarticles', $view == 'options');
JSubMenuHelper::addEntry('Log', 'index.php?option=com_braftonarticles&view=log', $view == 'log');

$controller->execute($task);
 
// Redirect if set by the controller
$controller->redirect();
?>
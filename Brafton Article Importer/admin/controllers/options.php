<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 * @since       1.6
 */
class BraftonArticlesControllerOptions extends JControllerAdmin {

	function apply() {
		$model = $this->getModel('options');
		$model->setOptions();
		$this->setRedirect('index.php?option=com_braftonarticles', $msg);
	}
}
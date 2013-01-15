<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.installer.installer');
jimport('joomla.filesystem.file');

class com_braftonarticlesInstallerScript
{ 
        /**
         * Called after any type of action
         *
         * @param   string  $route  Which action is happening (install|uninstall|discover_install)
         * @param   JAdapterInstance  $adapter  The object responsible for running this script
         *
         * @return  boolean  True on success
         */
        public function postflight($type, $parent){ 
			$installer = new JInstaller;
			$src = $parent->getParent()->getPath('source');
			$installer->install($src.DS.'plg_braftoncron');
		}
 
        /**
         * Called on uninstallation
         *
         * @param   JAdapterInstance  $adapter  The object responsible for running this script
         */
        public function uninstall(JAdapterInstance $adapter) {
			// Uninstalls s system plugin named plg_myplugin
			$db = JFactory::getDBO();
			$db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "braftoncron" AND `folder` = "system"');
			$id = $db->loadResult();
			if($id)
			{
				$installer = new JInstaller;
				$result = $installer->uninstall('plugin',$id,1);
				$status->plugins[] = array('name'=>'plg_srp','group'=>'system', 'result'=>$result);
			}
		}
}
<?php
/**
 * @package LiveUpdate
 * @copyright Copyright ©2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license GNU LGPLv3 or later <http://www.gnu.org/copyleft/lesser.html>
 */

defined('_JEXEC') or die();

/**
 * Live Update Component Storage Class
 * Allows to store the update data to a component's parameters. This is the most reliable method. 
 * Its configuration options are:
 * component	string	The name of the component which will store our data. If not specified the extension name will be used.
 * key			string	The name of the component parameter where the serialized data will be stored. If not specified "liveupdate" will be used.
 */
class LiveUpdateStorageComponent extends LiveUpdateStorage
{
	private static $component = null;
	private static $key = null;
	
	public function load($config)
	{
		if(!array_key_exists('component', $config)) {
			self::$component = $config['extensionName'];
		} else {
			self::$component = $config['component'];
		}

		if(!array_key_exists('key', $config)) {
			self::$key = 'liveupdate';
		} else {
			self::$key = $config['key'];
		}
		
		jimport('joomla.html.parameter');
		jimport('joomla.application.component.helper');
		$component =& JComponentHelper::getComponent(self::$component);
		$params = new JParameter($component->params);
		$data = $params->getValue(self::$key, '');
		
		if(!empty($data)) {
			if(function_exists('json_decode') && function_exists('json_encode')) {
				$data = json_decode($data);
			} elseif(function_exists('base64_encode') && function_exists('base64_decode')) {
				$data = unserialize(base64_decode($data));
			}
		}
		
		jimport('joomla.registry.registry');
		self::$registry = new JRegistry('update');
		
		self::$registry->loadINI($data);
	}
	
	public function save()
	{
		$data = self::$registry->toString('INI');
		
		if(function_exists('json_decode') && function_exists('json_encode')) {
			$data = json_decode($data);
		} elseif(function_exists('base64_encode') && function_exists('base64_decode')) {
			$data = base64_decode(serialize($data));
		}
		
		jimport('joomla.html.parameter');
		jimport('joomla.application.component.helper');
		$component =& JComponentHelper::getComponent(self::$component);
		$params = new JParameter($component->params);
		$params->setValue(self::$key, $data);

		$db =& JFactory::getDBO();
		
		if( version_compare(JVERSION,'1.6.0','ge') )
		{
			// Joomla! 1.6
			$data = $params->toString('JSON');
			$sql = 'UPDATE `#__extensions` SET `params` = '.$db->Quote($data).' WHERE '.
				"`element` = ".$db->Quote(self::$component)." AND `type` = 'component'";
		}
		else
		{
			// Joomla! 1.5
			$data = $params->toString('INI');
			$sql = 'UPDATE `#__components` SET `params` = '.$db->Quote($data).' WHERE '.
				"`option` = ".$db->Quote(self::$component)." AND `parent` = 0 AND `menuid` = 0";
		}

		$db->setQuery($sql);
		$db->query();
	}
} 
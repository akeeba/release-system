<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

use JComponentHelper;
use JFactory;
use JLoader;

defined('_JEXEC') or die;

/**
 * A helper class to quickly get and set the component parameters
 */
abstract class ComponentParams
{

	/**
	 * Cached component parameters
	 *
	 * @var \Joomla\Registry\Registry
	 */
	private static $params = null;

	/**
	 * Returns the value of a component configuration parameter
	 *
	 * @param   string $key     The parameter to get
	 * @param   mixed  $default Default value
	 *
	 * @return  mixed
	 */
	public static function getParam($key, $default = null)
	{
		if (!is_object(self::$params))
		{
			JLoader::import('joomla.application.component.helper');

			self::$params = JComponentHelper::getParams('com_ars');
		}

		return self::$params->get($key, $default);
	}

	/**
	 * Sets the value of a component configuration parameter
	 *
	 * @param   string $key    The parameter to set
	 * @param   mixed  $value  The value to set
	 *
	 * @return  void
	 */
	public static function setParam($key, $value)
	{
		self::setParams([$key => $value]);
	}

	/**
	 * Sets the value of multiple component configuration parameters at once
	 *
	 * @param   array  $params  The parameters to set
	 *
	 * @return  void
	 */
	public static function setParams(array $params)
	{
		if (!is_object(self::$params))
		{
			JLoader::import('joomla.application.component.helper');
			self::$params = JComponentHelper::getParams('com_ars');
		}

		foreach ($params as $key => $value)
		{
			self::$params->set($key, $value);
		}

		$db   = JFactory::getDBO();
		$data = self::$params->toString();

		$sql  = $db->getQuery(true)
				   ->update($db->qn('#__extensions'))
				   ->set($db->qn('params') . ' = ' . $db->q($data))
				   ->where($db->qn('element') . ' = ' . $db->q('com_ars'))
				   ->where($db->qn('type') . ' = ' . $db->q('component'));

		$db->setQuery($sql);

		try
		{
			$db->execute();
		}
		catch (\Exception $e)
		{
			// Don't sweat if it fails
		}
	}
}
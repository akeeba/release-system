<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

use FOF30\Container\Container;
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
	 * The component container
	 *
	 * @var   Container
	 */
	private static $container;

	/**
	 * Get the component's container
	 *
	 * @return  Container
	 */
	private static function getContainer()
	{
		if (is_null(self::$container))
		{
			self::$container = Container::getInstance('com_ars');
		}

		return self::$container;
	}

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
		$container = self::getContainer();

		return $container->params->get($key, $default);
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
		$container = self::getContainer();

		$container->params->setParams($params);
		$container->params->save();
	}
}
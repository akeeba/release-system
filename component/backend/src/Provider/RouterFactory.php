<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Provider;

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

class RouterFactory implements ServiceProviderInterface
{
	/**
	 * The module namespace
	 *
	 * @var  string
	 *
	 * @since   4.0.0
	 */
	private $namespace;

	/**
	 * DispatcherFactory constructor.
	 *
	 * @param   string  $namespace  The namespace
	 *
	 * @since   4.0.0
	 */
	public function __construct(string $namespace)
	{
		$this->namespace = $namespace;
	}

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function register(Container $container)
	{
		$container->set(
			RouterFactoryInterface::class,
			function (Container $container) {
				return new \Akeeba\Component\ARS\Administrator\Router\RouterFactory(
					$this->namespace,
					$container->get(DatabaseInterface::class),
					$container->get(MVCFactoryInterface::class)
				);
			}
		);
	}
}
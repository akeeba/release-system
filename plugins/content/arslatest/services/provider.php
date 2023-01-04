<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Content\Arslatest\Extension\Arslatest;

return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 */
	public function register(Container $container)
	{
		$container->registerServiceProvider(new MVCFactory('Akeeba\\Component\\ARS'));

		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$plugin     = PluginHelper::getPlugin('content', 'arslatest');
				$dispatcher = $container->get(DispatcherInterface::class);
				$factory    = $container->get(MVCFactoryInterface::class);

				return new Arslatest(
					$dispatcher,
					(array) $plugin,
					$factory
				);
			}
		);
	}
};

<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Plugin\Content\ARSLatest\Extension\Arslatest;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

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
				$pluginParams = PluginHelper::getPlugin('content', 'arslatest');
				$dispatcher   = $container->get(DispatcherInterface::class);
				$factory      = $container->get(MVCFactoryInterface::class);
				$plugin       = new Arslatest(
					$dispatcher,
					(array) $pluginParams
				);

				$plugin->setMVCFactory($factory);
				$plugin->setDatabase($container->get(DatabaseInterface::class));
				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};

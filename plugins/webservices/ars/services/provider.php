<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Akeeba\Plugin\WebServices\ARS\Extension\ARS;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$dispatcher   = $container->get(DispatcherInterface::class);
				$pluginParams = (array) PluginHelper::getPlugin('webservices', 'ars');
				$plugin       = new ARS(
					$dispatcher,
					$pluginParams
				);

				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};

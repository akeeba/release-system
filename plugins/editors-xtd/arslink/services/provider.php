<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Akeeba\Plugin\EditorsExtended\ARSLink\Extension\ARSLink;
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
				$pluginParams = (array) PluginHelper::getPlugin('editors-xtd', 'arslink');
				$plugin       = new ARSLink(
					$dispatcher,
					$pluginParams
				);

				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};

<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Helper\VersionLimits;
use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The ARS Stats module service provider.
 *
 * @since  7.1.0
 */
return new class implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   7.1.0
	 */
	public function register(Container $container)
	{
		// Only register the module in compatible environments
		if (!class_exists(VersionLimits::class) || !VersionLimits::isCompatible())
		{
			return;
		}

		$container->registerServiceProvider(new ModuleDispatcherFactory('\\Joomla\\Module\\Arsgraph'));
		$container->registerServiceProvider(new HelperFactory('\\Joomla\\Module\\Arsgraph\\Administrator\\Helper'));

		$container->registerServiceProvider(new Module());
	}
};

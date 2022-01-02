<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Extension;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Administrator\Service\Html\AkeebaReleaseSystem;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Psr\Container\ContainerInterface;

class ArsComponent extends MVCComponent implements
	BootableExtensionInterface, CategoryServiceInterface, RouterServiceInterface
{
	use HTMLRegistryAwareTrait;
	use RouterServiceTrait;
	use CategoryServiceTrait;

	public function boot(ContainerInterface $container)
	{
		$this->getRegistry()->register('ars', new AkeebaReleaseSystem());
	}

}
<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Service;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Site\Service\Router;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\Router\RouterInterface;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;

class RouterFactory extends \Joomla\CMS\Component\Router\RouterFactory
{
	use MVCFactoryAwareTrait;

	public function createRouter(CMSApplicationInterface $application, AbstractMenu $menu): RouterInterface
	{
		/** @var Router $router */
		$router = parent::createRouter($application, $menu);

		$router->setMVCFactory($this->getMVCFactory());

		return $router;
	}
}
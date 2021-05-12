<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Router;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Component\Router\RouterInterface;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;

class RouterFactory implements RouterFactoryInterface
{
	private $namespace;

	private $factory;

	private $db;

	public function __construct($namespace, DatabaseInterface $db = null, MVCFactoryInterface $factory)
	{
		$this->namespace = $namespace;
		$this->factory   = $factory;
		$this->db        = $db;
	}

	public function createRouter(CMSApplicationInterface $application, AbstractMenu $menu): RouterInterface
	{
		$className = trim($this->namespace, '\\') . '\\' . ucfirst($application->getName()) . '\\Service\\Router';

		if (!class_exists($className))
		{
			throw new \RuntimeException('No router available for this application.');
		}

		return new $className($application, $menu, $this->db, $this->factory);
	}
}
<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\ApiRouter;
use Joomla\Router\Route;

/**
 * Web Services adapter for Akeeba Release System (com_ars).
 *
 * @since  7.0.0
 */
class PlgWebservicesArs extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  7.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Registers the API routes in the application
	 *
	 * @param   ApiRouter  &$router  The API Routing object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onBeforeApiRoute(&$router)
	{
		$router->createCRUDRoutes(
			'v1/ars',
			'categories',
			['component' => 'com_ars']
		);

		$router->createCRUDRoutes(
			'v1/ars/categories',
			'categories',
			['component' => 'com_ars']
		);

		$router->createCRUDRoutes(
			'v1/ars/releases',
			'releases',
			['component' => 'com_ars']
		);

		$router->createCRUDRoutes(
			'v1/ars/items',
			'items',
			['component' => 'com_ars']
		);
	}
}

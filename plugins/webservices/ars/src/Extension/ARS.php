<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\WebServices\ARS\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\ApiRouter;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

/**
 * Web Services adapter for Akeeba Release System (com_ars).
 *
 * @since  7.0.0
 */
class ARS extends CMSPlugin implements SubscriberInterface
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
	 * @param   Event  $event
	 *
	 * @return  void
	 *
	 * @since   7.3.0
	 */
	public function beforeAPIRoute(Event $event): void
	{
		/** @var ApiRouter $router */
		[$router] = $event->getArguments();

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

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   7.3.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onBeforeApiRoute' => 'beforeAPIRoute',
		];
	}
}

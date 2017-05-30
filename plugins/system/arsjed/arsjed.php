<?php
/**
 * @package    AkeebaReleaseSystem
 * @subpackage plugins.arsdlid
 * @copyright  Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */

// Protect from unauthorized access
use FOF30\Container\Container;

defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

class plgSystemArsjed extends JPlugin
{
	/**
	 * Should this plugin be allowed to run? True if FOF can be loaded and the ARS component is enabled
	 *
	 * @var  bool
	 */
	private $enabled = true;

	/**
	 * The component container
	 *
	 * @var   Container
	 */
	protected $container;

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
		{
			$this->enabled = false;
		}

		// Do not run if Akeeba Subscriptions is not enabled
		JLoader::import('joomla.application.component.helper');

		if (!JComponentHelper::isEnabled('com_ars'))
		{
			$this->enabled = false;

			return;
		}

		if ($this->enabled)
		{
			$this->container = Container::getInstance('com_ars');
		}
	}

	public function onAfterInitialise()
	{
		if (!$this->enabled)
		{
			return true;
		}

		$app = JFactory::getApplication();

		$installat = base64_decode($app->input->get('installat', null, 'base64'));
		$installapp = $app->input->get('installapp', null, 'int');

		if (!empty($installapp) && !empty($installat))
		{
			$this->container->platform->setSessionVar('installat', $installat, 'arsjed');
			$this->container->platform->setSessionVar('installapp', $installapp, 'arsjed');
		}
	}
}

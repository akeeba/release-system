<?php
/**
 * @package    AkeebaReleaseSystem
 * @subpackage plugins.arsdlid
 * @copyright  Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */

// Protect from unauthorized access
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
			$session = JFactory::getSession();
			$session->set('installat', $installat, 'arsjed');
			$session->set('installapp', $installapp, 'arsjed');
		}
	}
}

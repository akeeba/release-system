<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
use Akeeba\ReleaseSystem\Site\Helper\Filter;
use FOF40\Container\Container;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\String\StringHelper;

defined('_JEXEC') or die();

class plgContentArsdlid extends CMSPlugin
{
	/**
	 * Cache of user IDs to Download IDs
	 *
	 * @var   array
	 */
	private static $cache = array();

	/**
	 * The component container
	 *
	 * @var   Container|null
	 */
	protected $container;

	/**
	 * Should this plugin be allowed to run? True if FOF can be loaded and the ARS component is enabled
	 *
	 * @var  bool
	 */
	private $enabled = true;

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		if (!defined('FOF40_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof40/include.php'))
		{
			$this->enabled = false;
		}

		// Do not run if Akeeba Subscriptions is not enabled
		if (!ComponentHelper::isEnabled('com_ars'))
		{
			$this->enabled = false;
		}

		if ($this->enabled)
		{
			$this->container = Container::getInstance('com_ars');
		}
	}


	public function onContentPrepare($context, &$article, &$params, $limitstart = 0)
	{
		if (!$this->enabled)
		{
			return true;
		}

		// Check whether the plugin should process or not
		if (StringHelper::strpos($article->text, 'downloadid') === false)
		{
			return true;
		}

		// Make sure our auto-loader is set up and ready
		Container::getInstance('com_ars');

		// Search for this tag in the content
		$regex = "#{[\s]*downloadid[\s]*}#s";

		$article->text = preg_replace_callback($regex, array('self', 'process'), $article->text);
	}

	private static function process(array $match): string
	{
		$ret       = '';
		$container = Container::getInstance('com_ars');
		$user      = $container->platform->getUser();

		if (!$user->guest)
		{
			if (!isset(self::$cache[$user->id]))
			{
				self::$cache[$user->id] = Filter::myDownloadID();
			}

			$ret = self::$cache[$user->id];
		}

		return $ret;
	}
}

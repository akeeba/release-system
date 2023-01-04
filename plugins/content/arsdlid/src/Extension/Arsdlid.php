<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Content\Arsdlid\Extension;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Site\Model\DlidlabelsModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\User;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\String\StringHelper;

class Arsdlid extends CMSPlugin implements SubscriberInterface
{
	use MVCFactoryAwareTrait;

	/**
	 * Cache of user IDs to Download IDs
	 *
	 * @var   array
	 */
	private static $cache = [];

	/** @var DlidlabelsModel */
	private static $model;

	public function __construct(&$subject, $config, MVCFactoryInterface $mvcFactory)
	{
		parent::__construct($subject, $config);

		$this->setMVCFactory($mvcFactory);

		self::$model = $this->mvcFactory->createModel('Dlidlabels', 'site');
	}

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 */
	public static function getSubscribedEvents(): array
	{
		// Only subscribe events if the component is installed and enabled
		if (!ComponentHelper::isEnabled('com_ars'))
		{
			return [];
		}

		return [
			'onContentPrepare' => 'onContentPrepare',
		];
	}

	private static function process(array $match): string
	{
		$user = Factory::getApplication()->getIdentity() ?: new User();

		if ($user->guest)
		{
			return '';
		}

		self::$cache[$user->id] = self::$cache[$user->id] ?? self::$model->myDownloadID($user);

		return self::$cache[$user->id];
	}

	public function onContentPrepare(Event $event)
	{
		[$context, $article, $params, $limitstart] = $event->getArguments();

		if (!ComponentHelper::isEnabled('com_ars'))
		{
			return;
		}

		// Check whether the plugin should process or not
		if (StringHelper::strpos($article->text, 'downloadid') === false)
		{
			return;
		}

		// Search for this tag in the content
		$regex = "#{[\s]*downloadid[\s]*}#s";

		$article->text = preg_replace_callback($regex, ['self', 'process'], $article->text);
	}
}
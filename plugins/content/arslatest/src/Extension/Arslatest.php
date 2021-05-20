<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Content\Arslatest\Extension;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Site\Model\DlidlabelsModel;
use Akeeba\Component\ARS\Site\Model\ItemsModel;
use Akeeba\Component\ARS\Site\Model\ReleasesModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\String\StringHelper;

class Arslatest extends CMSPlugin implements SubscriberInterface
{
	use MVCFactoryAwareTrait;

	/** @var bool Is this category prepared? */
	private $prepared = false;

	/** @var array Category titles to category IDs */
	private $categoryTitles = [];

	/** @var array The latest release per category, including files */
	private $categoryLatest = [];

	private $filesPerRelease = [];

	/**
	 * Should this plugin be allowed to run? True if the ARS component is enabled.
	 *
	 * @var  bool
	 */
	private $enabled = true;

	public function __construct(&$subject, $config, MVCFactoryInterface $mvcFactory)
	{
		parent::__construct($subject, $config);

		$this->setMVCFactory($mvcFactory);
		$this->enabled = ComponentHelper::isEnabled('com_ars');
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

	public function onContentPrepare(Event $event)
	{
		if (!$this->enabled)
		{
			return;
		}

		[$context, $row, $params, $limitstart] = $event->getArguments();

		$text = is_object($row) ? $row->text : $row;

		if (StringHelper::strpos($row->text, 'arslatest') !== false)
		{
			// Deferred initialisation
			if (!$this->prepared)
			{
				$this->initialise();
			}

			$regex = "#{arslatest(.*?)}#s";
			$text  = preg_replace_callback($regex, [$this, 'process'], $text);
		}

		if (is_object($row))
		{
			$row->text = $text;
		}
		else
		{
			$row = $text;
		}
	}

	/**
	 * preg_match callback to process each match
	 */
	private function process(array $match): string
	{
		[$op, $content, $pattern] = $this->analyzeString($match[1]);

		switch (strtolower($op))
		{
			case 'release':
				return $this->parseRelease($content);
				break;

			case 'release_link':
				return $this->parseReleaseLink($content);
				break;

			case 'item_link':
				return $this->parseItemLink($content, $pattern);
				break;

			case 'stream_link':
				return $this->parseStreamLink($content);
				break;

			case 'installfromweb':
				$app = Factory::getApplication();

				$installat  = $app->getSession()->get('arsjed.installat', null);
				$installapp = $app->getSession()->get('arsjed.installapp', null);

				if (!empty($installapp) && !empty($installat))
				{
					return $this->parseIFWLink();
				}

				return $this->parseStreamLink($content);
				break;

			default:
				return '';
		}
	}

	private function initialise(): void
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		/** @var ReleasesModel $model */
		$model = $this->mvcFactory->createModel('Releases', 'Site', ['ignore_request' => true]);
		$model->setState('filter.published', 1);
		$model->setState('filter.latest', true);
		$model->setState('filter.access', $user->getAuthorisedViewLevels());
		$model->setState('filter.allowUnauth', 1);
		$model->setState('list.start', 0);
		$model->setState('list.limit', 0);

		if (Multilanguage::isEnabled())
		{
			$model->setState('filter.language', [
				'*', $app->getLanguage()->getTag(),
			]);
		}

		$releases = $model->getItems() ?: [];

		/** @var object $release */
		foreach ($releases as $release)
		{
			$catTitle                                    = trim(strtoupper($release->cat_title));
			$this->categoryTitles[$catTitle]             = $release->category_id;
			$this->categoryLatest[$release->category_id] = $release;
		}

		$this->prepared = true;
	}

	private function analyzeString(string $string): array
	{
		$op      = '';
		$content = '';
		$pattern = '';

		$string = trim($string);
		$string = strtoupper($string);
		$parts  = explode(' ', $string, 2);

		if (count($parts) == 2)
		{
			$op = trim($parts[0]);
			if (in_array($op, ['RELEASE', 'RELEASE_LINK', 'STREAMLINK', 'INSTALLFROMWEB']))
			{
				$content = trim($parts[1]);
			}
			elseif ($op == 'ITEM_LINK')
			{
				$content    = trim($parts[1]);
				$firstquote = strpos($content, "'");

				if ($firstquote !== false)
				{
					$secondquote = strpos($content, "'", $firstquote + 1);
				}
				else
				{
					$secondquote = false;
				}

				if ($secondquote !== false)
				{
					$pattern = trim(substr($content, 0, $secondquote), "'");
					$content = trim(substr($content, $secondquote + 1));
				}
			}
			else
			{
				$op = '';
			}
		}

		if (empty($op))
		{
			$content = '';
		}

		if (empty($content))
		{
			$op = '';
		}

		if (empty($content))
		{
			$pattern = '';
		}

		return [$op, $content, $pattern];
	}

	/**
	 * @param   string  $content
	 *
	 * @return  object
	 */
	private function getLatestRelease(string $content): ?object
	{
		$catid = $this->categoryTitles[$content] ?? intval($content);

		return $this->categoryLatest[$catid] ?? null;
	}

	/**
	 * @param   string  $content
	 *
	 * @return  string
	 */
	private function parseRelease(string $content): string
	{
		$release = $this->getLatestRelease($content) ?: null;

		if (empty($release))
		{
			return '';
		}

		return $release->version ?? '';
	}

	/**
	 * @param   string  $content
	 *
	 * @return  string
	 */
	private function parseReleaseLink(string $content): string
	{
		$release = $this->getLatestRelease($content);

		if (empty($release))
		{
			return '';
		}

		return Route::_('index.php?option=com_ars&view=items&release_id=' . $release->id);
	}

	private function parseItemLink(string $content, string $pattern): string
	{
		$release = $this->getLatestRelease($content);

		if (empty($release))
		{
			return '';
		}

		$item = null;

		/** @var object $file */
		foreach ($this->getFilesForRelease($release->id ?? 0) as $file)
		{
			$fname = ($file->type == 'file') ? $file->filename : $file->url;
			$fname = strtoupper(basename($fname));

			if (!fnmatch($pattern, $fname))
			{
				continue;
			}

			$item = $file;
			break;
		}

		if (empty($item))
		{
			return '';
		}

		return Route::_('index.php?option=com_ars&view=item&task=download&format=raw&item_id=' . $item->id);
	}

	private function getFilesForRelease(int $release_id)
	{
		if (isset($this->filesPerRelease[$release_id]))
		{
			return $this->filesPerRelease[$release_id];
		}

		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		/** @var ItemsModel $model */
		$model = $this->mvcFactory->createModel('items', 'site', ['ignore_request' => true]);
		$model->setState('filter.release_id', $release_id);
		$model->setState('filter.published', 1);
		$model->setState('filter.access', $user->getAuthorisedViewLevels());
		$model->setState('filter.allowUnauth', 1);

		if (Multilanguage::isEnabled())
		{
			$model->setState('filter.language', [
				'*', $app->getLanguage()->getTag(),
			]);
		}

		$this->filesPerRelease[$release_id] = $model->getItems() ?: [];

		return $this->filesPerRelease[$release_id];
	}

	private function parseStreamLink(string $content): string
	{
		static $dlid = '';

		if (empty($dlid))
		{
			/** @var DlidlabelsModel $model */
			$model = $this->mvcFactory->createModel('DlidlabelsModel', 'site', ['ignore_request', true]);
			$dlid  = $model->myDownloadID();
		}

		$url = 'index.php?option=com_ars&view=update&task=download&format=raw&id=' . (int) $content;

		if (!empty($dlid))
		{
			$url .= '&dlid=' . $dlid;
		}

		$link = Route::_($url, false);

		return $link;
	}

	/**
	 * Provide the Install from Web link
	 *
	 * @return string
	 */
	private function parseIFWLink(): string
	{
		$app = Factory::getApplication();


		$installat  = $app->getSession()->get('arsjed.installat', null);
		$installapp = (int) ($app->getSession()->get('arsjed.installapp', null));

		// Find the stream ID based on the $installapp key
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->from('#__ars_updatestreams')
			->where($db->qn('jedid') . '=' . $db->q($installapp));
		$db->setQuery($query);
		$streamId = $db->loadResult();

		$downloadLink = $this->parseStreamLink($streamId);

		$link = $installat . '&installfrom=' . base64_encode($downloadLink);

		return $link;
	}

}
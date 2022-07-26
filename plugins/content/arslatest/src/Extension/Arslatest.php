<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\Content\Arslatest\Extension;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Administrator\Model\UpdatestreamsModel;
use Akeeba\Component\ARS\Site\Model\DlidlabelsModel;
use Akeeba\Component\ARS\Site\Model\ItemsModel;
use Akeeba\Component\ARS\Site\Model\ReleasesModel;
use Akeeba\Component\ARS\Site\Model\UpdateModel;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\String\StringHelper;

class Arslatest extends CMSPlugin implements SubscriberInterface
{
	use MVCFactoryAwareTrait;

	/**
	 * @var SiteApplication
	 */
	protected $app;

	/**
	 * @var DatabaseDriver
	 */
	protected $db;

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

	/**
	 * Information about the latest available item in a stream, indexed by the update stream ID.
	 *
	 * @var   array
	 * @since 6.0.1
	 */
	private $streamInfo = [];

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

		$context = $event->getArgument(0);
		$row     = $event->getArgument(1);

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

			case 'stream_release':
				return $this->parseStreamRelease($content, $pattern);
				break;

			case 'stream_release_link':
				return $this->parseStreamReleaseLink($content, $pattern);
				break;

			case 'stream_item_link':
				return $this->parseStreamItemLink($content, $pattern);
				break;

			case 'stream_link':
				return $this->parseStreamLink($content);
				break;

			default:
				return '';
		}
	}

	private function initialise(): void
	{
		$app  = $this->app;
		$user = $app->getIdentity() ?: new User();

		/**
		 * Get the latest releases (note: plural, releaseS!) per category. This is the raw data we'll work with.
		 */
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

		/**
		 * We may have multiple latest releases per category due to the way the query works (anything else would be too
		 * slow). We need to find out the singular latest release per category.
		 *
		 * We can do that by distributing the latest releases into an array per category ID. Then we will reduce each of
		 * these arrays of releases to a singular latest release using version_compare() against their version numbers.
		 */
		$temp = [];

		/** @var object $release */
		foreach ($releases as $release)
		{
			$temp[$release->category_id] = $temp[$release->category_id] ?? [];
			$temp[$release->category_id][] = $release;
		}

		$releases = array_map(
			function (array $items) {
				return array_reduce(
					$items,
					function (?object $carry, ?object $current) {
						if (is_null($carry))
						{
							return $current;
						}

						if (version_compare($current->version, $carry->version, 'gt'))
						{
							return $current;
						}

						return $carry;
					},
					null
				);
			},
			$temp
		);

		/**
		 * At this point we have an array of all latest releases for each known category. We will now populate the
		 * two arrays with the category titles and the category's latest release.
		 */
		/** @var object $release */
		foreach ($releases as $release)
		{
			$catTitle                                    = trim(strtoupper($release->cat_title));
			$this->categoryTitles[$catTitle]             = $release->category_id;
			$this->categoryLatest[$release->category_id] = $release;
		}

		/** @var UpdatestreamsModel $streamModel */
		$streamModel = $this->mvcFactory->createModel('Updatestreams', 'Administrator', ['ignore_request' => true]);
		$streamModel->setState('filter.published', 1);
		$streamModel->setState('list.start', 0);
		$streamModel->setState('list.limit', 0);

		foreach ($streamModel->getItems() ?: [] as $stream)
		{
			static $j3Env, $j4Env;

			if (!is_array($j3Env))
			{
				$j3Env = $this->getEnvironments('joomla/3.');
				$j4Env = $this->getEnvironments('joomla/4.');
			}

			/** @var UpdateModel $updateModel */
			$updateModel = $this->mvcFactory->createModel('Update', 'Site', ['ignore_request' => true]);
			$items       = $updateModel->getItems($stream->id);

			if (empty($items))
			{
				continue;
			}

			// Any Joomla 3 items
			$found   = false;
			$j3Items = array_filter($items, function ($item) use (&$found, $j3Env) {
				if ($found)
				{
					return false;
				}

				$found = !empty(array_intersect($item->environments, $j3Env));

				return $found;
			});

			// Any Joomla 4 items
			$found   = false;
			$j4Items = array_filter($items, function ($item) use (&$found, $j4Env) {
				if ($found)
				{
					return false;
				}

				$found = !empty(array_intersect($item->environments, $j4Env));

				return $found;
			});

			// Joomla 4 items which ARE NOT available on Joomla 3
			$found   = false;
			$altJ4Items = array_filter($items, function ($item) use (&$found, $j4Env, $j3Env) {
				if ($found)
				{
					return false;
				}

				$found = !empty(array_intersect($item->environments, $j4Env)) && empty(array_intersect($item->environments, $j3Env));

				return $found;
			});

			// Prefer the Joomla 4–specific items
			if (!empty($altJ4Items))
			{
				$j4Items = $altJ4Items;
			}

			$this->streamInfo[$stream->id] = [
				'ALL' => array_shift($items),
				'J3'  => empty($j3Items) ? null : array_shift($j3Items),
				'J4'  => empty($j4Items) ? null : array_shift($j4Items),
			];
		}

		$this->prepared = true;
	}

	private function getEnvironments($xmltitleMatches = 'joomla/3.')
	{
		$xmltitleMatches = '%' . trim($xmltitleMatches, '%') . '%';

		$db    = $this->db;
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__ars_environments'))
			->where($db->quoteName('xmltitle') . ' LIKE :search')
			->bind(':search', $xmltitleMatches);

		return $db->setQuery($query)->loadColumn() ?: [];
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

			if (in_array($op, ['RELEASE', 'RELEASE_LINK', 'STREAM_LINK', 'INSTALLFROMWEB']))
			{
				$content = trim($parts[1]);
			}
			elseif (in_array($op, ['STREAM_RELEASE', 'STREAM_RELEASE_LINK', 'STREAM_ITEM_LINK']))
			{
				$parts = explode(' ', trim($parts[1]), 2);

				if (count($parts) === 1)
				{
					$parts[] = 'ALL';
				}

				[$content, $pattern] = $parts;
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

		$content = empty($op) ? '' : $content;
		$op      = empty($content) ? '' : $op;
		$pattern = empty($content) ? '' : $pattern;

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

		return Route::_('index.php?option=com_ars&view=item&task=download&format=raw&category_id=' . $release->category_id . '&release_id=' . $release->id . '&item_id=' . $item->id);
	}

	private function parseStreamRelease(string $content, ?string $pattern): string
	{
		$stream_id = (int) $content;
		$pattern   = $pattern ?: '';

		if (!isset($this->streamInfo[$stream_id][$pattern]) || empty($this->streamInfo[$stream_id][$pattern]))
		{
			return '';
		}

		return $this->streamInfo[$stream_id][$pattern]->version;
	}

	private function parseStreamReleaseLink(string $content, ?string $pattern): string
	{
		$stream_id = (int) $content;
		$pattern   = $pattern ?: '';

		if (!isset($this->streamInfo[$stream_id][$pattern]) || empty($this->streamInfo[$stream_id][$pattern]))
		{
			return '';
		}

		$link = Route::_('index.php?option=com_ars&view=items&release_id=' . $this->streamInfo[$stream_id][$pattern]->release_id);

		return $link;
	}

	private function parseStreamItemLink(string $content, ?string $pattern)
	{
		$stream_id = (int) $content;
		$pattern   = $pattern ?: '';

		if (!isset($this->streamInfo[$stream_id][$pattern]) || empty($this->streamInfo[$stream_id][$pattern]))
		{
			return '';
		}

		return Route::_('index.php?option=com_ars&view=item&task=download&format=raw&category_id=' . $this->streamInfo[$stream_id][$pattern]->category . '&release_id=' . $this->streamInfo[$stream_id][$pattern]->release_id . '&item_id=' . $this->streamInfo[$stream_id][$pattern]->item_id);
	}

	private function getFilesForRelease(int $release_id)
	{
		if (isset($this->filesPerRelease[$release_id]))
		{
			return $this->filesPerRelease[$release_id];
		}

		$app  = $this->app;
		$user = $app->getIdentity() ?: new User();

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

		$url = 'index.php?option=com_ars&view=update&task=download&format=raw&item_id=' . (int) $content;

		if (!empty($dlid))
		{
			$url .= '&dlid=' . $dlid;
		}

		$link = Route::_($url, false);

		return $link;
	}
}

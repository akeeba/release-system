<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\ReleaseSystem\Site\View\Update\Ini $this */

use Akeeba\ReleaseSystem\Site\Helper\Router;
use FOF30\Date\Date;

if (!$this->published)
{
	die();
}

$rootURL    = rtrim(JURI::base(), '/');
$subpathURL = JURI::base(true);

if (!empty($subpathURL) && ($subpathURL != '/'))
{
	$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
}

if (!empty($this->items)):
	$item         = array_shift($this->items);

	$moreURL = $rootURL .
		str_replace('&amp;', '&', JRoute::_('index.php?option=com_ars&view=Items&release_id=' . $item->release_id));

	switch ($item->itemtype)
	{
		case 'file':
			$downloadURL = $rootURL .
				str_replace('&amp;', '&', Router::_('index.php?option=com_ars&view=Item&task=download&format=raw&id=' . $item->item_id));
			break;

		case 'link':
		default:
			$downloadURL = $item->url;
			break;
	}

	JLoader::import('joomla.utilities.date');
	$date = new Date($item->created);

	// Process supported environments
	$envs = [];

	if (!empty($item->environments) && is_array($item->environments))
	{
		foreach ($item->environments as $eid)
		{
			if (!isset($this->envs[ $eid ]))
			{
				$envs[$eid] = $this->envs[$eid];
			}

			$platforms[] = $this->envs[$eid]->xmltitle;
		}
	}
	else
	{
		$jVersion = new JVersion();
		$platforms = [
			'joomla/' . $jVersion->RELEASE,
			'php/' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION
		];
	}

	$platformKeys = array();

	foreach ($platforms as $platform)
	{
		$platformParts = explode('/', $platform, 2);

		switch (count($platformParts))
		{
			case 1:
				$platformName    = 'joomla';
				$platformVersion = $platformParts[0];
				break;

			default:
				$platformName    = $platformParts[0];
				$platformVersion = $platformParts[1];
				break;
		}

		$platformKeys[] = $platformName . '/' . $platformVersion;
	}

	@ob_end_clean();

	// Custom header for SiteGround's SuperCacher. The default value caches the
	// output for 5 minutes.
	JFactory::getApplication()->setHeader('X-Akeeba-Expire-After', 300);

	?>
; Live Update provision file
; Generated on <?= gmdate('Y-m-d H:i:s') ?> GMT
software="<?php echo $item->cat_title ?>"
version="<?php echo $item->version; ?>"
link="<?php echo $downloadURL; ?>"
date="<?php echo $date->format('Y-m-d'); ?>"
releasenotes="<?php echo str_replace("\n", '', str_replace("\r", '', JHtml::_('content.prepare', $item->release_notes))); ?>"
infourl="<?php echo $moreURL ?>"
md5="<?php echo $item->md5 ?>"
sha1="<?php echo $item->sha1 ?>"
platforms="<?php echo implode(',', $platformKeys) ?>"
<?php else: ?>
; Live Update provision file
; No updates are available!
<?php endif; ?>

<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\ReleaseSystem\Site\View\Update\Xml $this */

use Akeeba\ReleaseSystem\Site\Helper\Router;
use Akeeba\ReleaseSystem\Site\Helper\Filter;

$rootURL    = rtrim(JURI::base(), '/');
$subpathURL = JURI::base(true);

if (!empty($subpathURL) && ($subpathURL != '/'))
{
	$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
}

$tag = "<" . "?xml version=\"1.0\" encoding=\"utf-8\"" . "?" . ">";

$dlid = trim($this->input->getCmd('dlid', ''));

if (!empty($dlid))
{
	$dlid = Filter::reformatDownloadID($dlid);
}

if (!empty($dlid))
{
	$dlid = '&dlid=' . $dlid;
}
else
{
	$dlid = '';
}

// Clear everything before starting the output
@ob_end_clean();

// Custom header for SiteGround's SuperCacher. The default value caches the
// output for 5 minutes.
JFactory::getApplication()->setHeader('X-Akeeba-Expire-After', 300);

require_once JPATH_SITE . '/components/com_ars/router.php';
ComArsRouter::$routeRaw  = false;
ComArsRouter::$routeHtml = false;

$jVersion = new JVersion;

?><?php echo $tag; ?>
<!-- Update stream generated automatically by Akeeba Release System on <?= gmdate('Y-m-d H:i:s') ?> GMT -->
<jedupdate version="1">
<?php
foreach ($this->items as $item):
	switch ($item->itemtype)
	{
		case 'file':
			$downloadURL = $rootURL .
				Router::_('index.php?option=com_ars&view=Item&task=download&format=raw&id=' . $item->item_id . $dlid);

			$basename    = basename($item->filename);

			if (substr(strtolower($basename), -4) == '.zip')
			{
				$format = 'zip';
			}
			elseif (substr(strtolower($basename), -4) == '.tgz')
			{
				$format = 'tgz';
			}
			elseif (substr(strtolower($basename), -7) == '.tar.gz')
			{
				$format = 'tgz';
			}
			elseif (substr(strtolower($basename), -4) == '.tar')
			{
				$format = 'tar';
			}
			elseif (substr(strtolower($basename), -8) == '.tar.bz2')
			{
				$format = 'tbz2';
			}
			elseif (substr(strtolower($basename), -4) == '.tbz')
			{
				$format = 'tbz2';
			}
			elseif (substr(strtolower($basename), -5) == '.tbz2')
			{
				$format = 'tbz2';
			}
			else
			{
				continue;
			}

			$downloadURL .= '&amp;dummy=my.' . $format;
			break;

		case 'link':
		default:
			$downloadURL = $item->url;
			$basename    = basename($item->url);
			break;
	}

	if (!empty($item->environments) && is_array($item->environments))
	{
		$platforms = array();

		foreach ($item->environments as $eid)
		{
			if (isset($this->envs[ $eid ]))
			{
				$platforms[] = $this->envs[ $eid ]->xmltitle;
			}
		}

		if (empty($platforms))
		{
			$platforms = [
				'joomla/' . $jVersion->RELEASE,
				'php/' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION
			];
		}
	}
	else
	{
		$platforms = [
			'joomla/' . $jVersion->RELEASE,
			'php/' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION
		];
	}
?>
	<download_link><?php echo $downloadURL ?></download_link>
	<version><?php echo $item->version ?></version>
	<compatibility>
		<?php
		foreach ($platforms as $platform):
			$platformParts   = explode('/', $platform, 2);

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

			if ($platformName != 'joomla')
			{
				continue;
			}

			$platformVersion = str_replace('.', '', $platformVersion);

			?>
			<version><?php echo $platformVersion ?></version>
		<?php
		endforeach;
		?>
	</compatibility>
<?php break; endforeach; ?>
</jedupdate>
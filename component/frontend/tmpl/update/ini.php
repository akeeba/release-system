<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\Component\ARS\Site\View\Update\IniView $this */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

if (count($this->items))
{
	/** @var object $item */
	$item = array_shift($this->items);
	[$downloadURL, $format] = $this->getDownloadUrl($item);
	$parsedPlatforms = $this->getParsedPlatforms($item, false);
	$platformKeys    = array_map(function ($x) {
		return $x[0] . '/' . $x[1];
	}, $parsedPlatforms['platforms']);
	$platformKeys    = array_merge($platformKeys, array_map(function ($x) {
		return 'php/' . $x;
	}, $parsedPlatforms['php']));

	$moreURL = Route::_('index.php?option=com_ars&view=items&&release_id=' . $item->release_id . '&category_id=' . $item->category, false, Route::TLS_IGNORE, true);
	$date    = clone Factory::getDate($item->created);
}

@ob_end_clean();

if (empty($this->items) || !$item->published)
{
	echo <<< INI
; Live Update provision file
; No updates are available!
INI;

	return;
}

$infoUrl = Route::_(
		'index.php?option=com_ars&view=Items&release_id=' . $item->release_id,
		true, Route::TLS_IGNORE, true
);

$releaseNotes = HTMLHelper::_('ars.preProcessMessage', $item->release_notes);

$releaseNotes = $this->compactDisplay ? '' : (str_replace("\n", '', str_replace("\r", '', $releaseNotes)));
?>
<?php if(!$this->compactDisplay): ?>
	; Live Update provision file
	; Generated on <?= gmdate('Y-m-d H:i:s') ?> GMT
<?php endif; ?>
software="<?= str_replace('"', '\\"', $item->cat_title) ?>"
version="<?= str_replace('"', '\\"', $item->version) ?>"
link="<?= $downloadURL ?>"
date="<?= $date->format('Y-m-d') ?>"
releasenotes="<?= str_replace('"', '\\"', $releaseNotes) ?>"
infourl="<?= $moreURL ?>"
<?php if ($this->showChecksums): ?>
md5="<?= $item->md5 ?>"
sha1="<?= $item->sha1 ?>"
sha256="<?= $item->sha256 ?>"
sha384="<?= $item->sha384 ?>"
sha512="<?= $item->sha512 ?>"
<?php endif ?>
platforms="<?= implode(',', $platformKeys) ?>"
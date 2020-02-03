<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\ReleaseSystem\Site\View\Update\Ini $this */

use Akeeba\ReleaseSystem\Admin\Helper\Format;
use Akeeba\ReleaseSystem\Site\Helper\Router;
use FOF30\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

if (count($this->items))
{
	/** @var object $item */
	$item = array_shift($this->items);
	list($downloadURL, $format) = $this->getDownloadUrl($item);
	$parsedPlatforms = $this->getParsedPlatforms($item);
	$platformKeys    = array_map(function ($x) {
		return $x[0] . '/' . $x[1];
	}, $parsedPlatforms['platforms']);
	$platformKeys    = array_merge($platformKeys, array_map(function ($x) {
		return 'php/' . $x;
	}, $parsedPlatforms['php']));

	$moreURL = Route::_('index.php?option=com_ars&view=Items&release_id=' . $item->release_id, false, Route::TLS_IGNORE, true);
	$date    = new Date($item->created);
}

@ob_end_clean();
?>
@if (!$this->published || empty($this->items))
	; Live Update provision file
	; No updates are available!
@else
	; Live Update provision file
	; Generated on {{ gmdate('Y-m-d H:i:s') }} GMT
	software="{{ $item->cat_title }}"
	version="{{ $item->version }}"
	link="{{ $downloadURL }}"
	date="{{ $date->format('Y-m-d') }}"
	releasenotes="{{ str_replace("\n", '', str_replace("\r", '', Format::preProcessMessage($item->release_notes))) }}"
	infourl="{{ $moreURL }}"
	md5="{{ $item->md5 }}"
	sha1="{{ $item->sha1 }}"
	@if($this->showChecksums)
		@foreach (['sha256', 'sha384', 'sha512'] as $hash)
			@unless(empty($item->{$hash}))
				{{$hash}}="{{ $item->{$hash} }}"
			@endunless
		@endforeach
	@endif
	platforms="{{ implode(',', $platformKeys) }}"
@endif
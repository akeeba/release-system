<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\ReleaseSystem\Site\View\Update\Ini $this */

use Akeeba\ReleaseSystem\Site\Helper\Router;
use Akeeba\ReleaseSystem\Site\Helper\Router as RouterHelper;
use FOF30\Date\Date;
use Joomla\CMS\Router\Route;

if (count($this->items))
{
	/** @var object $item */
	$item = array_shift($this->items);
	list($downloadURL, $format) = $this->getDownloadUrl($item);
	$parsedPlatforms = $this->getParsedPlatforms($item, false);
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

if (!$this->published || empty($this->items))
{
	echo <<< INI
; Live Update provision file
; No updates are available!
INI;

	return;
}

$infoUrl = RouterHelper::_(
		'index.php?option=com_ars&view=Items&release_id=' . $item->release_id,
		true, Route::TLS_IGNORE, true
);
?>
; Live Update provision file
; Generated on {{ gmdate('Y-m-d H:i:s') }} GMT
software="{{ $item->cat_title }}"
version="{{ $item->version }}"
link="{{ $downloadURL }}"
date="{{ $date->format('Y-m-d') }}"
releasenotes="<a href=\"{{ $infoUrl }}\">{{ $infoUrl }}</a>"
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
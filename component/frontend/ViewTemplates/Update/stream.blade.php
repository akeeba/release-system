<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\ReleaseSystem\Site\View\Update\Xml $this */

$showChecksums = isset($this->showChecksums) ? $this->showChecksums : false;

$streamTypeMap = [
		'components' => 'component',
		'libraries'  => 'library',
		'modules'    => 'module',
		'packages'   => 'package',
		'plugins'    => 'plugin',
		'files'      => 'file',
		'templates'  => 'template',
];

// Clear everything before starting the output
@ob_end_clean();

echo '<' . '?';
?>xml version = "1.0" encoding = "utf-8" <?php echo '?' . '>' ?>
<!-- {{ gmdate('Y-m-d H:i:s') }} -->
<updates>
	<?php foreach ($this->items as $item):
	$parsedPlatforms = $this->getParsedPlatforms($item);
	foreach ($parsedPlatforms['platforms'] as $platform):
	list($platformName, $platformVersion) = $platform;
	list($downloadUrl, $format) = $this->getDownloadUrl($item);
	?>
	<update>
		<name><![CDATA[{{{ $item->name }}}]]></name>
		<description><![CDATA[{{{ $item->name }}}]]></description>
		<element>{{{ $item->element }}}</element>
		<type>{{{ $streamTypeMap[$item->type] }}}</type>
		<version>{{{ $item->version }}}</version>
		<infourl title="{{ $item->cat_title }} {{ $item->version }}"><![CDATA[{{{ \Akeeba\ReleaseSystem\Site\Helper\Router::_(
				'index.php?option=com_ars&view=Items&release_id=' . $item->release_id,
				true, \Joomla\CMS\Router\Route::TLS_IGNORE, true
			) }}}]]>
		</infourl>
		<downloads>
			<downloadurl type="full" format="{{{ $format }}}"><![CDATA[{{{ $downloadUrl }}}]]></downloadurl>
		</downloads>
		<tags>
			<tag>{{{ $item->maturity }}}</tag>
		</tags>
		<maintainer><![CDATA[{{{ $this->container->platform->getConfig()->get('sitename') }}}]]></maintainer>
		<maintainerurl>{{{ \Joomla\CMS\Uri\Uri::base() }}}</maintainerurl>
		<section>Updates</section>
		<targetplatform name="{{{ $platformName }}}" version="{{{ $platformVersion }}}" />
		<?php foreach(['md5', 'sha1', 'sha256', 'sha384', 'sha512'] as $checksum):
		if ($showChecksums && !empty($item->{$checksum})): ?>
		<{{ $checksum }}>{{{ $item->{$checksum} }}}</{{ $checksum }}>
	<?php endif; endforeach;
	if(($platformName == 'joomla') && (version_compare($platformVersion, '2.5', 'lt'))): ?>
	<client_id>{{ (int) $item->client_id }}</client_id>
	<?php else: ?>
	<client>{{ (int) $item->client_id }}</client>
	<?php endif; ?>
	<folder>{{ $item->folder ?? '' }}</folder>
	@foreach($parsedPlatforms['php'] as $phpVersion)
		<ars-phpcompat version="<?php echo $phpVersion ?>" />
		@endforeach
		</update>
		<?php endforeach; endforeach; ?>
</updates>

<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\ReleaseSystem\Site\View\Update\Xml $this */

$streamTypeMap = [
	'components' => 'component',
	'libraries'  => 'library',
	'modules'    => 'module',
	'packages'   => 'package',
	'plugins'    => 'plugin',
	'files'      => 'file',
	'templates'  => 'template'
];

@ob_end_clean();
echo '<' . '?';
?>xml version = "1.0" encoding = "utf-8" <?php echo '?' . '>' ?>
<!-- Update stream generated automatically by Akeeba Release System on {{ gmdate('Y-m-d H:i:s') }} -->
<extensionset category="{{ ucfirst($this->category) }}" name="{{ ucfirst($this->category) }}"
              description="@lang('COM_ARS_STREAM_UPDATETYPE_' . strtoupper($this->category))">
    @foreach($this->items as $item)
        <extension
                name="{{{ $item->name }}}"
                element="{{{ $item->element }}}"
                type="{{{ $streamTypeMap[$item->type] }}}"
                version="{{{ $item->version }}}"
                detailsurl="{{ \Akeeba\ReleaseSystem\Site\Helper\Router::_(
				  	'index.php?option=com_ars&view=update&format=xml&task=stream&id=' . $item->id . $this->dlidRequest,
				  	true, \Joomla\CMS\Router\Route::TLS_IGNORE, true
				) }}" />
	@endforeach
</extensionset>

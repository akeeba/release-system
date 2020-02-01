<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\ReleaseSystem\Site\View\Update\Xml $this */

@ob_end_clean();
echo '<' . '?';
?>xml version = "1.0" encoding = "utf-8" <?php echo '?' . '>' ?>
<!-- Update stream generated automatically by Akeeba Release System on {{ gmdate('Y-m-d H:i:s') }} -->
<extensionset name="<?php echo $this->updates_name ?>" description="<?php echo $this->updates_desc ?>">
	@foreach (['components', 'libraries', 'modules', 'packages', 'plugins', 'files', 'templates'] as $category)
		<category name="{{ ucfirst($category) }}"
				  description="@lang('LBL_UPDATETYPES_' . strtoupper($category))"
				  category="{{ $category }}"
				  ref="{{ \Akeeba\ReleaseSystem\Site\Helper\Router::_(
				  	'index.php?option=com_ars&view=update&format=xml&task=category&id=' . $category . $this->dlidRequest,
				  	true, \Joomla\CMS\Router\Route::TLS_IGNORE, true
				) }}" />
	@endforeach
</extensionset>

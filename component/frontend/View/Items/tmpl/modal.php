<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \FOF30\View\DataView\Form $this */

// Render the filter sidebar
$this->getContainer()->toolbar->setRenderFrontendSubmenu(true);

// Turn off routing before displaying the form to prevent JPagination's call to JRoute from removing the layoun and tmpl
// query string parameters which are critical for pagination links to work.
if (!class_exists('ComArsRouter'))
{
	require_once JPATH_COMPONENT . '/Helper/ComArsRouter.php';
}

ComArsRouter::$routeHtml = false;

// Now render the form
echo $this->getRenderedForm();

// Re-enable ARS routing
ComArsRouter::$routeHtml = true;

$function = $this->input->getCmd('function', 'arsSelectItem');
?>
<script type="text/javascript">
	function arsItemsProxy(id, title)
	{
		if (window.parent) window.parent.<?php echo $this->escape($function); ?>(id, title);
	}
</script>
<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \FOF30\View\DataView\Form $this */

echo $this->getRenderedForm();

$function = $this->input->getCmd('function', 'arsSelectItem');
?>
<script type="text/javascript">
	function arsItemsProxy(id, title)
	{
		if (window.parent) window.parent.<?php echo $this->escape($function); ?>(id, title);
	}
</script>
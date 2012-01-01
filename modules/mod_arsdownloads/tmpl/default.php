<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die();

?>
<table class="adminTable">
<?php echo $params->get('pretext',''); ?>
<?php foreach($items as $i): ?>
	<tr>
		<td width="200"><b><?php echo htmlentities($i->name) ?></b> <?php echo $i->version ?></td>
		<td class="button4">
			<a class="readon" href="<?php echo JRoute::_('index.php?option=com_ars&view=download&format=raw&id='.$i->id) ?>">
				<span>Download</span>
			</a>
		</td>
		<td width="100">
			<a class="readon" href="<?php echo JRoute::_('index.php?option=com_ars&view=release&id='.$i->release_id) ?>">
				<span>View all</span>
			</a>
		</td>
	</tr>
<?php endforeach; ?>
</table>
<?php echo $params->get('posttext',''); ?>
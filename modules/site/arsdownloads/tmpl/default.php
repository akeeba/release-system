<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die();

?>
	<table class="adminTable">
		<?php echo $params->get('pretext', ''); ?>
		<?php foreach ($items as $i): ?>
			<tr>
				<td width="200"><b><?php echo htmlentities($i->name) ?></b> <?php echo $i->version ?></td>
				<td class="button4">
					<a class="readon"
					   href="<?php echo Route::_('index.php?option=com_ars&view=Item&format=raw&id=' . $i->id) ?>">
						<span><?php echo Text::_('MOD_ARSDOWNLOADS_LBL_DOWNLOAD'); ?></span>
					</a>
				</td>
				<td width="100">
					<a class="readon"
					   href="<?php echo Route::_('index.php?option=com_ars&view=Items&release_id=' . $i->release_id) ?>">
						<span><?php echo Text::_('MOD_ARSDOWNLOADS_LBL_VIEWALL') ?></span>
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php echo $params->get('posttext', ''); ?>

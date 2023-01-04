<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

/**
 * @var Registry $params
 * @var object[] $items
 */

$preText  = $params->get('pretext', '');
$postText = $params->get('posttext', '');
?>
<div class="mod_arsdownloads">
	<?php if (!empty($preText)): ?>
		<div class="mod_arsdownloads_pretext my-3">
			<?= $preText ?>
		</div>
	<?php endif; ?>

	<table class="table table-striped mod_arsdownloads_table">
		<?php foreach ($items as $item):
			switch ($item->maturity)
			{
				case 'stable':
					$maturityClass = 'bg-success';
					break;

				case 'rc':
					$maturityClass = 'bg-info';
					break;

				case 'beta':
					$maturityClass = 'bg-warning';
					break;

				case 'alpha':
					$maturityClass = 'bg-danger';
					break;

				default:
					$maturityClass = 'bg-dark';
					break;
			}
			?>
			<tr class="mod_arsdownloads_item">
				<th scope="row">
					<span>
						<?php echo htmlentities($item->name) ?>
					</span>
					<?php echo $item->version ?>
					<?php if ($item->maturity != 'stable'): ?>
						<span class="badge <?= $maturityClass ?>">
							<?= Text::_('COM_ARS_RELEASES_MATURITY_' . $item->maturity) ?>
						</span>
					<?php endif ?>
				</th>
				<td>
					<a class="btn btn-primary btn-sm"
					   href="<?= Route::_('index.php?option=com_ars&view=item&format=raw&item_id=' . $item->item_id) ?>">
						<span class="fa fa-download-file"></span>
						<?= Text::_('MOD_ARSDOWNLOADS_LBL_DOWNLOAD'); ?>
					</a>
				</td>
				<td>
					<a class="btn btn-link"
					   href="<?php echo Route::_('index.php?option=com_ars&view=items&release_id=' . $item->release_id) ?>">
						<?= Text::_('MOD_ARSDOWNLOADS_LBL_VIEWALL') ?>
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>

	<?php if (!empty($postText)): ?>
		<div class="mod_arsdownloads_posttext my-3">
			<?= $postText ?>
		</div>
	<?php endif; ?>

</div>

<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * @var  HtmlView $this
 * @var  object   $item
 */

use Akeeba\Component\ARS\Site\View\Items\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('bootstrap.collapse', '.ars-collapse');

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

<div class="ars-release-<?= $item->id ?> ars-release-<?= $this->category->is_supported ? 'supported' : 'unsupported' ?> mb-2 border-bottom">
	<h3 class="text-muted d-flex mt-0 mb-2">
		<span class="pe-3">
			<?= $this->escape($this->category->title) ?>
			<?= $this->escape($item->version) ?>
		</span>
		<span class="badge <?= $maturityClass ?>">
            <?= Text::_('COM_ARS_RELEASES_MATURITY_' . $item->maturity) ?>
        </span>
	</h3>

	<div class="text-muted d-flex mb-2">
		<div class="flex-grow-1">
			<strong><?= Text::_('COM_ARS_RELEASE_LBL_RELEASEDON') ?></strong>:
			<?= HTMLHelper::_('ars.formatDate', $item->created, true, Text::_('DATE_FORMAT_LC2')) ?>
		</div>
		<div>
			<button class="btn btn-dark btn-sm release-info-toggler" type="button"
					data-bs-target="#ars-release-<?= $item->id ?>-info" data-bs-toggle="collapse"
					aria-expanded="false" aria-controls="ars-release-<?= $item->id ?>-info">
				<span class="fa fa-info-circle"></span>
				<?= Text::_('COM_ARS_RELEASES_MOREINFO') ?>
			</button>
		</div>
	</div>

	<div id="ars-release-<?= $item->id ?>-info" class="collapse mb-2">
		<div class="card card-body">
			<?= HTMLHelper::_('ars.preProcessMessage', $item->notes, 'com_ars.release_notes') ?>
		</div>
	</div>
</div>

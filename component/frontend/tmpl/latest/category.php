<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Site\View\Latest\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * @var HtmlView $this
 * @var object   $category
 */

// Do I have a release?
if (!isset($this->releases[$category->id]))
{
	return;
}

$release     = $this->releases[$category->id];
$released    = \Joomla\CMS\Factory::getDate($release->created);
$release_url = $this->getReleaseUrl($release);

switch ($release->maturity)
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

$environments = ($this->params->get('show_environments', 1) == 1) ? $this->getModel('Releases')->getEnvironments($release) : [];

?>

<div class="ars-release-<?= $release->id ?> ars-release-<?= $category->is_supported ? 'supported' : 'unsupported' ?> mb-3 border-bottom">

	<h4 class="d-flex mt-0 mb-2 h3">
		<span class="pe-3">
			<?= $this->escape($category->title) ?>
			<?= $this->escape($release->version) ?>
		</span> <span class="badge <?= $maturityClass ?>">
            <?= Text::_('COM_ARS_RELEASES_MATURITY_' . $release->maturity) ?>
        </span>
	</h4>

	<div class="ars-latest-category">
		<div class="ars-category-description">
			<?= HTMLHelper::_('ars.preProcessMessage', $category->description, 'com_ars.category_description') ?>
		</div>
	</div>

	<table class="table table--striped">
		<tr>
			<th scope="row">
				<?= Text::_('COM_ARS_RELEASES_FIELD_MATURITY') ?>
			</th>
			<td colspan="2">
				<?= Text::_('COM_ARS_RELEASES_MATURITY_' . strtoupper($release->maturity)) ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?= Text::_('COM_ARS_RELEASE_LBL_RELEASEDON') ?>
			</th>
			<td colspan="2">
				<?= HTMLHelper::date($released, Text::_('DATE_FORMAT_LC2')) ?>
			</td>
		</tr>
		<?php if (!empty($environments)): ?>
			<th scope="row">
				<?= Text::_('COM_ARS_ITEM_FIELD_ENVIRONMENTS') ?>
			</th>
			<td colspan="2">
				<?php foreach ($environments as $environment): ?>
					<span class="badge bg-light text-dark ars-environment-icon"><?= $environment ?></span>
				<?php endforeach; ?>
			</td>
		<?php endif ?>

		<?php foreach ($this->items[$release->id] ?? [] as $downloadItem): ?>
			<?= $this->loadAnyTemplate('Latest/item', true, ['item' => $downloadItem]); ?>
		<?php endforeach; ?>
	</table>

	<p class="mt-1">
		<a href="<?= $release_url ?>" class="btn btn-secondary btn-sm">
			<?= Text::_('COM_ARS_RELEASE_LBL_VIEW_ITEMS') ?>
		</a>
	</p>
</div>

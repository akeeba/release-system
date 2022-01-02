<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Site\View\Categories\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * @var  HtmlView $this
 * @var object    $item
 * @var int       $id
 * @var ?int      $Itemid
 */

$category_url = Route::_(sprintf("index.php?option=com_ars&view=releases&category_id=%s&Itemid=%s", $item->id, $this->Itemid));
$user         = Factory::getApplication()->getIdentity();

if (!in_array($item->access, $user->getAuthorisedViewLevels()))
{
	$category_url = $item->redirect_unauth ?: $this->params->get('no_access_url', 'index.php');

	if ((strpos($category_url, 'http://') !== 0) && (strpos($category_url, 'https://') !== 0))
	{
		$category_url = Route::_($category_url);
	}
}

HTMLHelper::_('bootstrap.collapse', '.ars-collapse');
?>
<div class="ars-category-<?= $this->escape($id) ?> ars-category-<?= $item->is_supported ? 'supported' : 'unsupported' ?> my-3">
	<h4 class="<?= $item->type == 'bleedingedge' ? 'warning' : '' ?> my-0">
		<a href="<?= htmlentities($category_url) ?>">
			<?= $this->escape($item->title) ?>
		</a>
	</h4>
	<p>
		<button class="btn btn-info btn-sm" type="button"
				data-bs-toggle="collapse" data-bs-target="#ars-category-<?= $this->escape($id) ?>-info"
				aria-expanded="false" aria-controls="ars-category-<?= $this->escape($id) ?>-info">
			<span class="fa fa-info-circle"></span>
			<?= Text::_('COM_ARS_RELEASES_MOREINFO') ?>
		</button>

		<a href="<?= htmlentities($category_url) ?>" class="btn btn-sm btn-dark">
			<span class="fa fa-folder"></span>
			<?= Text::_('COM_ARS_CATEGORIES_LBL_AVAILABLEVERSIONS') ?>
		</a>
	</p>

	<div class="collapse" id="ars-category-<?= $this->escape($id) ?>-info">
		<div class="ars-browse-category card card-body">
			<div class="ars-category-description">
				<?= HTMLHelper::_('ars.preProcessMessage', $item->description, 'com_ars.category_description') ?>
			</div>
		</div>
	</div>
</div>

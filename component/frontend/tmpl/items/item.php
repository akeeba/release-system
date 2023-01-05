<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * @var  HtmlView $this
 * @var object    $item
 */

use Akeeba\Component\ARS\Site\View\Items\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

[$downloadUrl, $isDownloadUrl] = $this->getItemUrl($item);
$directLink    = $this->directlink && $isDownloadUrl;
$directLinkURL = $directLink ? $this->getDirectLink($item, $downloadUrl) : '';

HTMLHelper::_('bootstrap.collapse', '.ars-collapse');

$environments = $item->environments;

if (is_numeric($environments))
{
	$environments = [$environments];
}
elseif (is_string($environments))
{
	$environments = @json_decode($environments);
}

?>

<div class="ars-item-<?= $item->id ?> mb-3 pb-1 border-bottom border-secondary">
	<h4>
		<a href="<?= $downloadUrl ?>"<?= $isDownloadUrl ? ' download' : '' ?>><?= $this->escape($item->title) ?></a>
	</h4>
	<?php if (!(empty($environments) || !$this->params->get('show_environments',1))): ?>
	<p>
		<?php foreach($environments as $environment):
			$title = $this->environmentTitle((int) $environment);
			if (is_null($title)) continue;
			?>
		<span class="badge bg-secondary ars-environment-icon">
			<?= $this->escape($title) ?>
		</span>
		<?php endforeach; ?>
	</p>
	<?php endif; ?>
	<div class="my-2 d-flex">
		<div class="flex-grow-1">
			<a href="<?= $downloadUrl ?>" rel="nofollow"<?= $isDownloadUrl ? ' download' : '' ?> class="text-success">
				<code><?= $this->escape(basename(($item->type == 'file') ? $item->filename : $item->url)) ?></code>
			</a>
			<?php if (!empty($directLinkURL)): ?>
				<a href="<?= $directLinkURL ?>" class="directlink hasTip px-2 text-info"
				   rel="nofollow" title="<?= $this->escape($this->directlink_description) ?>">
					<?= Text::_('COM_ARS_ITEM_LBL_DIRECTLINK') ?>
				</a>
			<?php endif; ?>
		</div>
		<div>
			<a href="<?= $downloadUrl ?>" class="btn btn-primary btn-sm " rel="nofollow"<?= $isDownloadUrl ? ' download' : '' ?>>
				<span class="fa fa-file-download"></span>
				<?= Text::_('COM_ARS_ITEM_LBL_DOWNLOAD') ?>
			</a>

			<button class="btn btn-dark btn-sm release-info-toggler" type="button"
					data-bs-target="#ars-item-<?= $item->id ?>-info" data-bs-toggle="collapse"
					aria-expanded="false" aria-controls="ars-item-<?= $item->id ?>-info">
				<span class="fa fa-info-circle"></span>
				<?= Text::_('COM_ARS_RELEASES_MOREINFO') ?>
			</button>
		</div>
	</div>

	<div id="ars-item-<?= $item->id ?>-info" class="collapse">
		<div class="card card-body">
			<table class="table table-striped ars-release-properties">
				<?php if(!((!$this->params->get('show_downloads', 1)))): ?>
				<tr>
					<td>
						<?= Text::_('COM_ARS_ITEM_FIELD_HITS') ?>
					</td>
					<td>
						<?= Text::plural('COM_ARS_RELEASE_LBL_TIME', $item->hits) ?>
					</td>
				</tr>
				<?php endif; ?>

				<?php if(!(empty($item->filesize) || !$this->params->get('show_filesize',1))): ?>
				<tr>
					<td>
						<?= Text::_('COM_ARS_ITEM_FIELD_FILESIZE') ?>
					</td>
					<td>
						<?= HTMLHelper::_('ars.sizeFormat', (int)$item->filesize) ?>
					</td>
				</tr>
				<?php endif; ?>

				<?php if(!(empty($item->md5) || !$this->params->get('show_md5',1))): ?>
				<tr>
					<td>
						<?= Text::_('COM_ARS_ITEM_FIELD_MD5') ?>
					</td>
					<td>
						<?= $this->escape($item->md5) ?>
					</td>
				</tr>
				<?php endif; ?>

				<?php if(!(empty($item->sha1) || !$this->params->get('show_sha1',1))): ?>
				<tr>
					<td>
						<?= Text::_('COM_ARS_ITEM_FIELD_SHA1') ?>
					</td>
					<td>
						<?= $this->escape($item->sha1) ?>
					</td>
				</tr>
				<?php endif; ?>

				<?php if(!(empty($item->sha256) || !$this->params->get('show_sha256',1))): ?>
				<tr>
					<td>
						<?= Text::_('COM_ARS_ITEM_FIELD_SHA256') ?>
					</td>
					<td>
						<?= $this->escape($item->sha256) ?>
					</td>
				</tr>
				<?php endif; ?>

				<?php if(!(empty($item->sha384) || !$this->params->get('show_sha384',1))): ?>
				<tr>
					<td>
						<?= Text::_('COM_ARS_ITEM_FIELD_SHA384') ?>
					</td>
					<td>
						<?= $this->escape($item->sha384) ?>
					</td>
				</tr>
				<?php endif; ?>

				<?php if(!(empty($item->sha512) || !$this->params->get('show_sha512',1))): ?>
				<tr>
					<td>
						<?= Text::_('COM_ARS_ITEM_FIELD_SHA512') ?>
					</td>
					<td>
						<?= $this->escape($item->sha512) ?>
					</td>
				</tr>
				<?php endif; ?>

				<?php if(!(empty($environments) || !$this->params->get('show_environments',1))): ?>
				<tr>
					<td>
						<?= Text::_('COM_ARS_ITEM_FIELD_ENVIRONMENTS') ?>
					</td>
					<td>
						<?php foreach($environments as $environment):
							$title = $this->environmentTitle((int) $environment);
							if (is_null($title)) continue;
							?>
							<span class="badge bg-secondary ars-environment-icon">
								<?= $this->escape($title) ?>
							</span>
						<?php endforeach; ?>
					</td>
				</tr>
				<?php endif; ?>
			</table>

			<?php if (!empty($item->description)): ?>
			<p class="ars-item-description small">
				<?= HTMLHelper::_('ars.preProcessMessage', $item->description, 'com_ars.item_description') ?>
			</p>
			<?php endif; ?>

			<div>
				<a href="<?= $downloadUrl ?>" class="btn btn-primary" rel="nofollow" <?= $isDownloadUrl ? ' download' : '' ?>>
					<?= Text::_('COM_ARS_ITEM_LBL_DOWNLOAD') ?>
				</a>
				<?php if (!empty($directLinkURL)): ?>
				<a href="<?= $directLinkURL ?>" class="directlink hasTip ms-3"
				   rel="nofollow" title="<?= $this->escape($this->directlink_description) ?>">
					<?= Text::_('COM_ARS_ITEM_LBL_DIRECTLINK') ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

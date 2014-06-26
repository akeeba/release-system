<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var ArsViewRelease $this */

// Incoming var injected by loadAnyTemplate
if(!isset($userAccess)) $userAccess = array();

JHtml::_('behavior.tooltip');

$Itemid = empty($Itemid) ? "" : "&Itemid=$Itemid";
$download_url = AKRouter::_('index.php?option=com_ars&view=download&format=raw&id='.$item->id.$Itemid);

$directlink = false;

// Should I display the link to unauthorized user?
if(!($item->cat_show_unauth && $item->rel_show_unauth && $item->show_unauth_links))
{
	// Ok check fail, let's see if I am inside an access view
	if(!in_array($item->access, $userAccess))
	{
		// Nope! Go away!
		return;
	}
}

if ($this->directlink)
{
	$basename = ($item->type == 'file') ? $item->filename : $item->url;

	foreach ($this->directlink_extensions as $ext)
	{
		if (substr($basename, -strlen($ext)) == $ext)
		{
			$directlink = true;
			break;
		}
	}

	if ($directlink)
	{
		$directlink_url = $download_url .
				(strstr($download_url, '?') !== false ? '&' : '?') .
				'dlid='.$this->dlid.'&jcompat=my'.$ext;
	}
}
?>
<div class="ars-item-<?php echo $item->id ?> well">
	<h4>
		<a href="<?php echo htmlentities($download_url) ?>">
			<?php echo $this->escape($item->title) ?>
		</a>
	</h4>

	<dl class="dl-horizontal ars-release-properties">
		<?php if($this->pparams->get('show_downloads', 1)): ?>
		<dt>
			<?php echo JText::_('LBL_ITEMS_HITS') ?>:
		</dt>
		<dd>
			<?php echo JText::sprintf( ($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits) ?>
		</dd>
		<?php endif;?>

		<?php if(!empty($item->filesize) && $this->pparams->get('show_filesize',1)): ?>
		<dt>
			<?php echo JText::_('LBL_ITEMS_FILESIZE') ?>:
		</dt>
		<dd>
			<?php echo ArsHelperHtml::sizeFormat($item->filesize) ?>
		</dd>
		<?php endif; ?>

		<?php if(!empty($item->md5) && $this->pparams->get('show_md5',1)): ?>
		<dt>
			<?php echo JText::_('LBL_ITEMS_MD5') ?>:
		</dt>
		<dd>
			<?php echo $item->md5 ?>
		</dd>
		<?php endif; ?>

		<?php if(!empty($item->sha1) && $this->pparams->get('show_sha1',1)): ?>
		<dt>
			<?php echo JText::_('LBL_ITEMS_SHA1') ?>:
		</dt>
		<dd>
			<?php echo $item->sha1 ?>
		</dd>
		<?php endif; ?>

		<?php if(!empty($item->environments) && $this->pparams->get('show_environments',1)): ?>
		<dt>
			<?php echo JText::_('LBL_ITEMS_ENVIRONMENTS') ?>:
		</dt>
		<dd>
			<?php echo $item->environments ?>
		</dd>
		<?php endif; ?>

	</dl>

	<?php if (!empty($item->description)): ?>
	<div class="ars-item-description well small">
		<?php echo ArsHelperHtml::preProcessMessage($item->description, 'com_ars.item_description'); ?>
	</div>
	<?php endif; ?>

	<div>
		<div class="pull-left">
			<p class="readmore">
				<a href="<?php echo htmlentities($download_url); ?>" class="btn btn-primary">
					<?php echo JText::_('LBL_ITEM_DOWNLOAD') ?>
				</a>
			</p>
		</div>
		<?php if ($directlink): ?>
		<div class="pull-left">
			&nbsp;
			<a rel="nofollow" href="<?php echo htmlentities($directlink_url); ?>"
			   class="directlink hasTip" title="<?php echo $this->directlink_description ?>"
			>
				<?php echo JText::_('COM_ARS_LBL_ITEM_DIRECTLINK') ?>
			</a>
		</div>
		<?php endif; ?>
		<div class="clearfix"></div>
	</div>
</div>
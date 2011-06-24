<?php
/**
 * @package LiveUpdate
 * @copyright Copyright ©2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license GNU LGPLv3 or later <http://www.gnu.org/copyleft/lesser.html>
 */

defined('_JEXEC') or die();
?>

<div class="liveupdate">

	<?php if(!$this->updateInfo->supported): ?>
	<div class="liveupdate-notsupported">
		<h3><?php echo JText::_('LIVEUPDATE_NOTSUPPORTED_HEAD') ?></h3>
		
		<p><?php echo JText::_('LIVEUPDATE_NOTSUPPORTED_INFO'); ?></p>
		<p class="liveupdate-url">
			<?php echo $this->escape($this->updateInfo->extInfo->updateurl) ?>
		</p>
		<p><?php echo JText::sprintf('LIVEUPDATE_NOTSUPPORTED_ALTMETHOD', $this->escape($this->updateInfo->extInfo->title)); ?></p>
		<p class="liveupdate-buttons">
			<button onclick="window.location='<?php echo $this->requeryURL ?>'" ><?php echo JText::_('LIVEUPDATE_REFRESH_INFO') ?></button>
		</p>
	</div>
	
	<?php elseif($this->updateInfo->stuck):?>
	<div class="liveupdate-stuck">
		<h3><?php echo JText::_('LIVEUPDATE_STUCK_HEAD') ?></h3>
		
		<p><?php echo JText::_('LIVEUPDATE_STUCK_INFO'); ?></p>
		<p><?php echo JText::sprintf('LIVEUPDATE_NOTSUPPORTED_ALTMETHOD', $this->escape($this->updateInfo->extInfo->title)); ?></p>
		
		<p class="liveupdate-buttons">
			<button onclick="window.location='<?php echo $this->requeryURL ?>'" ><?php echo JText::_('LIVEUPDATE_REFRESH_INFO') ?></button>
		</p>
	</div>
	
	<?php else: ?>
	<?php
		$class = $this->updateInfo->hasUpdates ? 'hasupdates' : 'noupdates';
		$auth = $this->config->getAuthorization();
		$auth = empty($auth) ? '' : '?'.$auth;
	?>
	<?php if($this->needsAuth): ?>
	<p class="liveupdate-error-needsauth">
		<?php echo JText::_('LIVEUPDATE_ERROR_NEEDSAUTH'); ?>
	</p>
	<?php endif; ?>
	<div class="liveupdate-<?php echo $class?>">
		<h3><?php echo JText::_('LIVEUPDATE_'.strtoupper($class).'_HEAD') ?></h3>
		<div class="liveupdate-infotable">
			<div class="liveupdate-row row0">
				<span class="liveupdate-label"><?php echo JText::_('LIVEUPDATE_CURRENTVERSION') ?></span>
				<span class="liveupdate-data"><?php echo $this->updateInfo->extInfo->version ?></span>
			</div>
			<div class="liveupdate-row row1">
				<span class="liveupdate-label"><?php echo JText::_('LIVEUPDATE_LATESTVERSION') ?></span>
				<span class="liveupdate-data"><?php echo $this->updateInfo->version ?></span>
			</div>
			<div class="liveupdate-row row0">
				<span class="liveupdate-label"><?php echo JText::_('LIVEUPDATE_LATESTRELEASED') ?></span>
				<span class="liveupdate-data"><?php echo $this->updateInfo->date ?></span>
			</div>
			<div class="liveupdate-row row1">
				<span class="liveupdate-label"><?php echo JText::_('LIVEUPDATE_DOWNLOADURL') ?></span>
				<span class="liveupdate-data"><a href="<?php echo $this->updateInfo->downloadURL.$auth?>"><?php echo $this->escape($this->updateInfo->downloadURL)?></a></span>
			</div>
		</div>
		
		<p class="liveupdate-buttons">
			<?php if($this->updateInfo->hasUpdates):?>
			<?php $disabled = $this->needsAuth ? 'disabled="disabled"' : ''?>
			<button <?php echo $disabled?> onclick="window.location='<?php echo $this->runUpdateURL ?>'" ><?php echo JText::_('LIVEUPDATE_DO_UPDATE') ?></button>
			<?php endif;?>
			<button onclick="window.location='<?php echo $this->requeryURL ?>'" ><?php echo JText::_('LIVEUPDATE_REFRESH_INFO') ?></button>
		</p>
	</div>
	
	<?php endif; ?>

	<p class="liveupdate-poweredby">
		Powered by <a href="https://www.akeebabackup.com/software/akeeba-live-update.html">Akeeba Live Update</a>
	</p>

</div>
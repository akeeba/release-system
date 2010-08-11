<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

$lang =& JFactory::getLanguage();
$icons_root = JURI::base().'components/com_ars/assets/images/';

$groups = array('basic','insight','tools','update');
?>
<div id="cpanel">
	<div class="ak_cpanel_modules" id="ak_cpanel_modules">
		<h3>TODO</h3>
		<div class="ak_cpanel_status_cell">
			TODO
		</div>
	</div>

	<div class="ak_cpanel_main_container">
	<?php foreach($groups as $group): ?>
		<?php if(array_key_exists($group, $this->icondefs)): ?>
		<?php if(!count($this->icondefs[$group])) continue; ?>
		<div class="ak_cpanel_header ui-widget-header ui-corner-tl ui-corner-tr">
			<?php echo JText::_('LBL_ARS_CPANEL_'.  strtoupper($group)); ?>
		</div>
		<div class="ak_cpanel_icons ui-widget-content ui-corner-br ui-corner-bl">
			<?php foreach($this->icondefs[$group] as $icon): ?>
			<div class="icon">
				<a href="<?php echo 'index.php?option=com_ars'.
					(is_null($icon['view']) ? '' : '&amp;view='.$icon['view']).
					(is_null($icon['task']) ? '' : '&amp;task='.$icon['task']); ?>">
				<div class="ak-icon ak-icon-<?php echo $icon['icon'] ?>">&nbsp;</div>
				<span><?php echo $icon['label']; ?></span>
				</a>
			</div>
			<?php endforeach; ?>
			<div class="ak_clr_left"></div>
		</div>
		<?php endif; ?>
	<?php endforeach; ?>
	</div>
</div>

<div class="ak_clr"></div>

<p>
	<?php echo JText::sprintf('ARS_COPYRIGHT', date('y')); ?><br/>
	<?php echo JText::_('ARS_LICENSE'); ?>
</p>

<script type="text/javascript">
akeeba.jQuery(document).ready(function($){
	$('#ak_cpanel_modules').accordion();
});
</script>

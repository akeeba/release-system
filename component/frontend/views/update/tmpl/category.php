<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$tag = "<"."?xml version=\"1.0\" encoding=\"utf-8\""."?".">";

@ob_end_clean();
@header('Content-type: application/xml');
?><?php echo $tag; ?>
<!-- Update stream generated automatically by Akeeba Release System -->
<extensionset category="<?php echo ucfirst($this->category)?>" name="<?php echo ucfirst($this->category)?>" description="<?php echo JText::_('LBL_UPDATETYPES_'.strtoupper($this->category)); ?>">
<?php if(!empty($this->items)) foreach($this->items as $item): ?>	<extension name="<?php echo $item->name ?>" element="<?php echo $item->element ?>" type="<?php echo $item->type ?>" version="<?php echo $item->version?>" detailsurl="<?php echo rtrim(JURI::base(),'/').AKRouter::_('index.php?option=com_ars&view=update&format=xml&task=stream&id='.$item->id) ?>" />
<?php endforeach ?>
</extensionset>
<?php die(); ?>
<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$categories = array('components','libraries','modules','packages','plugins','files','templates');
$tag = "<"."?xml version=\"1.0\" encoding=\"utf-8\""."?".">";

@ob_end_clean();
@header('Content-type: application/xml');
?><?php echo $tag; ?>
<!-- Update stream generated automatically by Akeeba Release System -->
<extensionset name="<?php echo $this->updates_name ?>" description="<?php echo $this->updates_desc ?>">
<?php foreach($categories as $category): ?>	<category name="<?php echo ucfirst($category)?>" description="<?php echo JText::_('LBL_UPDATETYPES_'.strtoupper($category)); ?>" category="<?php echo $category ?>" ref="<?php echo rtrim(JURI::base(),'/').AKRouter::_('index.php?option=com_ars&view=update&format=xml&task=category&id='.$category) ?>" />
<?php endforeach ?>
</extensionset><?php die(); ?>
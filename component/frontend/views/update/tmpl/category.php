<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die();

$rootURL = rtrim(JURI::base(),'/');
$subpathURL = JURI::base(true);
if(!empty($subpathURL) && ($subpathURL != '/')) {
	$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
}

$tag = "<"."?xml version=\"1.0\" encoding=\"utf-8\""."?".">";

$dlid = trim(JRequest::getCmd('dlid',''));
if($dlid) {
	if(strlen($dlid) > 32) $dlid = substr($dlid,0,32);
	$dlid = '&dlid='.$dlid;
} else {
	$dlid = '';
}

$streamTypeMap = array(
	'components' => 'component',
	'libraries' => 'library',
	'modules' => 'module',
	'packages' => 'package',
	'plugins' => 'plugin',
	'files' => 'file',
	'templates' => 'template'
);

@ob_end_clean();
@header('Content-type: application/xml');

?><?php echo $tag; ?>
<!-- Update stream generated automatically by Akeeba Release System -->
<extensionset category="<?php echo ucfirst($this->category)?>" name="<?php echo ucfirst($this->category)?>" description="<?php echo JText::_('LBL_UPDATETYPES_'.strtoupper($this->category)); ?>">
<?php if(!empty($this->items)) foreach($this->items as $item): 
$url = $rootURL.AKRouter::_('index.php?option=com_ars&view=update&format=xml&task=stream&id='.$item->id.$dlid);
$url=str_replace('&', '&amp;', $url);
if(substr($url,-4) != '.xml') $url .= (strpos($url, '?') ? '&amp;' : '?').'dummy=extension.xml';
?>	<extension name="<?php echo $item->name ?>" element="<?php echo $item->element ?>" type="<?php echo $streamTypeMap[$item->type] ?>" version="<?php echo $item->version?>" detailsurl="<?php echo $url ?>" />
<?php endforeach ?>
</extensionset>
<?php JFactory::getApplication()->close(); ?>
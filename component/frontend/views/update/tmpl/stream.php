<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$rootURL = rtrim(JURI::base(),'/');
$subpathURL = JURI::base(true);
if(!empty($subpathURL) && ($subpathURL != '/')) {
	$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
}

$tag = "<"."?xml version=\"1.0\" encoding=\"utf-8\""."?".">";

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
<updates>
<?php
foreach($this->items as $item):
	switch($item->itemtype) {
		case 'file':
			$downloadURL = $rootURL.AKRouter::_('index.php?option=com_ars&view=download&id='.$item->item_id);
			$basename = basename($item->filename);
			break;
		case 'link':
		default:
			$downloadURL = $item->url;
			$basename = basename($item->url);
			break;
	}
	if( substr(strtolower($basename),-4) == '.zip' ) {
		$format = 'zip';
	} elseif( substr(strtolower($basename),-4) == '.tgz' ) {
		$format = 'tgz';
	} elseif( substr(strtolower($basename),-7) == '.tar.gz' ) {
		$format = 'tgz';
	} elseif( substr(strtolower($basename),-4) == '.tar' ) {
		$format = 'tar';
	} elseif( substr(strtolower($basename),-8) == '.tar.bz2' ) {
		$format = 'tbz2';
	} elseif( substr(strtolower($basename),-4) == '.tbz' ) {
		$format = 'tbz2';
	} elseif( substr(strtolower($basename),-5) == '.tbz2' ) {
		$format = 'tbz2';
	} else {
		continue;
	}
	
	$platforms = array('1.6','1.7');
	foreach($platforms as $platform):
?>
	<update>
		<name><?php echo $item->alias ?></name>
		<description><?php echo $item->name ?></description>
		<element><?php echo $item->element ?></element>
		<type><?php echo $streamTypeMap[$item->type]; ?></type>
		<version><?php echo $item->version ?></version>
		<infourl title="<?php echo $item->cat_title.' '.$item->version ?>"><?php echo $rootURL.AKRouter::_('index.php?option=com_ars&view=release&id='.$item->release_id) ?></infourl>
		<downloads>
			<downloadurl type="full" format="<?php echo $format ?>"><?php echo $downloadURL?></downloadurl>
		</downloads>
		<tags>
			<tag><?php echo $item->maturity ?></tag>
		</tags>
		<maintainer><?php echo JFactory::getConfig()->getValue('config.sitename'); ?></maintainer>
		<maintainerurl><?php echo JURI::base();?></maintainerurl>
		<section>Updates</section>
		<targetplatform name="joomla" version="<?php echo $platform?>" />
		<client_id><?php echo $item->client_id?></client_id>
		<folder><?php echo empty($item->folder) ? '' : $item->folder?></folder>>
	</update>
<?php
	endforeach;
endforeach; ?>
</updates><?php die(); ?>
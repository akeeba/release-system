<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$tag = "<"."?xml version=\"1.0\" encoding=\"utf-8\""."?".">";
?><?php echo $tag; ?>

<updates>
<?php
foreach($this->items as $item):
	switch($item->type) {
		case 'file':
			$downloadURL = rtrim(JURI::base(),'/').JRoute::_('index.php?option=com_ars&view=download&id='.$item->item_id);
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
	} else {
		continue;
	}
?>
	<update>
		<name><?php echo $item->alias ?></name>
		<description><?php echo $item->name ?></description>
		<element><?php echo $item->element ?></element>
		<type><?php echo $item->type ?></type>
		<version><?php echo $item->version ?></version>
		<infourl title="<?php echo $item->cat_title.' '.$item->release_id ?>"><?php echo rtrim(JURI::base(),'/').JRoute::_('index.php?option=com_ars&view=release&id='.$item->release_id) ?></infourl>
		<downloads>
			<downloadurl type="full" format="<?php echo $format ?>"><?php echo $downloadURL?></downloadurl>
		</downloads>
		<targetplatform name="joomla" version="1.6" />
	</update>
<?php endforeach ?>
</updates>
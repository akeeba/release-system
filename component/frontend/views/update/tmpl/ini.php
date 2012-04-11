<?php
/**
 * @package Updater
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

$rootURL = rtrim(JURI::base(),'/');
$subpathURL = JURI::base(true);
if(!empty($subpathURL) && ($subpathURL != '/')) {
	$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
}

if( !empty($this->items) ):
	$item = array_shift($this->items);

	$rootURL = rtrim(JURI::base(),'/');
	$subpathURL = JURI::base(true);
	if(!empty($subpathURL) && ($subpathURL != '/')) {
		$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
	}
	$moreURL = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_ars&view=release&id='.$item->release_id));
	switch($item->itemtype) {
		case 'file':
			$downloadURL = $rootURL.str_replace('&amp;', '&', AKRouter::_('index.php?option=com_ars&view=download&id='.$item->item_id));
			break;
		case 'link':
		default:
			$downloadURL = $item->url;
			break;
	}
	jimport('joomla.utilities.date');
	$date = new JDate($item->created);

@ob_end_clean();
@header('Content-type: text/plain');
?>; Live Update provision file
software="<?php echo $item->cat_title ?>"
version="<?php echo $item->version; ?>"
link="<?php echo $downloadURL; ?>"
date="<?php echo $date->toFormat('%Y-%m-%d'); ?>"
releasenotes="<?php echo str_replace("\n", '', str_replace("\r", '', $item->release_notes)); ?>"
infourl="<?php echo $moreURL ?>"
md5="<?php echo $item->md5 ?>"
sha1="<?php echo $item->sha1 ?>"
<?php else: ?>
; Live Update provision file
; No updates are available!
<?php endif; ?>
<?php JFactory::getApplication()->close(); ?>
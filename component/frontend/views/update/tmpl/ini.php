<?php
/**
 * @package Updater
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

if( !empty($this->items) ):
	$item = array_shift($this->items);
	switch($item->itemtype) {
		case 'file':
			$downloadURL = rtrim(JURI::base(),'/').str_replace('&amp;', '&', AKRouter::_('index.php?option=com_ars&view=download&id='.$item->item_id));
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
<?php else: ?>
; Live Update provision file
; No updates are available!
<?php endif; ?>
<?php JFactory::getApplication()->close(); ?>
<?php
/**
 * @package Updater
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id: ini.php 4 2010-03-27 18:22:25Z nicholas $
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

if( !empty($this->items) ):
	$item = array_shift($this->items);
	switch($item->type) {
		case 'file':
			$downloadURL = rtrim(JURI::base(),'/').JRoute::_('index.php?option=com_ars&view=download&id='.$item->item_id);
			break;
		case 'link':
		default:
			$downloadURL = $item->url;
			break;
	}
	jimport('joomla.utilities.date');
	$date = new JDate($item->created);

?>; Live Update provision file
software="<?php echo $item->cat_title ?>"
version="<?php echo $item->version; ?>"
link="<?php echo $downloadURL; ?>"
date="<?php echo $date->toFormat('%Y-%m-%d'); ?>"
<?php endif; ?>
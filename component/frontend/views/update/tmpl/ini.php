<?php
/**
 * @package Updater
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/** @var ArsViewUpdate $this */

if(!$this->published) {
	die();
}

$rootURL = rtrim(JURI::base(),'/');
$subpathURL = JURI::base(true);
if(!empty($subpathURL) && ($subpathURL != '/')) {
	$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
}

if( !empty($this->items) ):
	$item = array_shift($this->items);

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
	JLoader::import('joomla.utilities.date');
	$date = new JDate($item->created);

	// Process supported environments
	$item->environments = @json_decode($item->environments);
	if(!empty($item->environments) && is_array($item->environments)) {
		static $envs = array();

		$platforms = array();

		if(!class_exists('ArsModelEnvironments')) {
			require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/environments.php';
		}
		foreach($item->environments as $eid) {
			if (! isset( $envs[$eid] ) ) {
				$envs[$eid] = F0FModel::getTmpInstance('Environments','ArsModel')
					->getItem($eid);
			}

			$platforms[] = $envs[$eid]->xmltitle;
		}
	} else {
		$platforms = array('joomla/2.5');
	}

	$platformKeys = array();

	foreach($platforms as $platform) {
		$platformParts = explode('/',$platform, 2);
		switch(count($platformParts)) {
			case 1:
				$platformName = 'joomla';
				$platformVersion = $platformParts[0];
				break;
			default:
				$platformName = $platformParts[0];
				$platformVersion = $platformParts[1];
				break;
		}
		$platformKeys[] = $platformName.'/'.$platformVersion;
	}

@ob_end_clean();
// Custom header for SiteGround's SuperCacher. The default value caches the
// output for 5 minutes.
@header('X-Akeeba-Expire-After: 300');

?>; Live Update provision file
; Generated on <?=gmdate('Y-m-d H:i:s')?> GMT
software="<?php echo $item->cat_title ?>"
version="<?php echo $item->version; ?>"
link="<?php echo $downloadURL; ?>"
date="<?php echo $date->format('Y-m-d'); ?>"
releasenotes="<?php echo str_replace("\n", '', str_replace("\r", '', JHtml::_('content.prepare', $item->release_notes))); ?>"
infourl="<?php echo $moreURL ?>"
md5="<?php echo $item->md5 ?>"
sha1="<?php echo $item->sha1 ?>"
platforms="<?php echo implode(',', $platformKeys) ?>"
<?php else: ?>
; Live Update provision file
; No updates are available!
<?php endif; ?>

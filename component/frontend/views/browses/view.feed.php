<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsViewBrowses extends F0FView
{
	function  display($tpl = null) {
		$this->loadHelper('router');

		$document = JFactory::getDocument();
		$document->setLink(JRoute::_('index.php?option=com_ars&view=categories'));

		$model  = $this->getModel();
		$model->processFeedData();
		if(!count($model->itemList)) return;
		foreach($model->itemList as $sectionName => $section)
		{
			if(!empty($section)) foreach($section as $cat) {
				if(empty($cat->release)) continue;

				$item = new JFeedItem();
				$user = JFactory::getUser($cat->release->created_by);

				$item->author = $user->name;
				$item->title = $this->escape($cat->title.' '.$cat->release->version);
				$item->category = $this->escape($cat->title);
				$item->date = date('r', strtotime($cat->release->created));
				if(!empty($cat->release->description)) {
					$item->description = $cat->release->description;
					if(!empty($cat->release->notes)) $item->description .= '<hr/>';
				} else {
					$item->description = '';
				}

				if(!empty($cat->release->notes)) {
					$item->description .= $cat->release->notes;
				}

				$item->link = $this->escape(JURI::base().AKRouter::_('index.php?option=com_ars&view=release&id='.$cat->release->id));
				$item->pubDate = date('r');

				$document->addItem($item);
			}
		}
	}
}

class ArsViewBrowse extends ArsViewBrowses {}
<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.view');

class ArsViewCategory extends JView
{
	function  display($tpl = null) {
		$model  = $this->getModel();

		$document = JFactory::getDocument();
		$document->setLink(AKRouter::_('index.php?option=com_ars&view=category&id='.$model->item->id));

		if(empty($model->itemList)) return;
		foreach($model->itemList as $rel)
		{
			$item = new JFeedItem();
			$user = JFactory::getUser($rel->created_by);

			$item->author = $user->name;
			$item->title = $this->escape($model->item->title.' '.$rel->version);
			$item->category = $this->escape($model->item->title);
			$item->date = date('r', strtotime($rel->created));
			
			if(!empty($rel->description)) {
				$item->description = $rel->description;
				if(!empty($rel->notes)) $item->description .= '<hr/>';
			} else {
				$item->description = '';
			}
			
			if(!empty($rel->notes)) {
				$item->description .= $rel->notes;
			}
			
			$item->link = $this->escape(JURI::base().AKRouter::_('index.php?option=com_ars&view=release&id='.$rel->id));
			$item->pubDate = date('r');

			$document->addItem($item);
		}
	}
}
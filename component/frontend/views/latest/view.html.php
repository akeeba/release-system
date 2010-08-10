<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.view');

class ArsViewLatest extends JView
{
	function  display($tpl = null) {
		$model = $this->getModel();
		$task = $model->getState('task','cmd');

		// Call the relevant method
		$method_name = 'on'.ucfirst($task);
		if(method_exists($this, $method_name)) {
			$this->$method_name();
		} else {
			$this->onDisplay();
		}

		// Add the CSS/JS definitions
		$doc = JFactory::getDocument();
		if($doc->getType() == 'html') {
			require_once JPATH_COMPONENT.DS.'helpers'.DS.'includes.php';
			ArsHelperIncludes::includeMedia();
		}

		// Pass the data
		$this->assignRef( 'items',		$model->itemList );
		$this->assignRef( 'item',		$model->item );
		$this->assignRef( 'lists',		$model->lists );
		$this->assignRef( 'pagination',	$model->pagination );

		// Pass the parameters
		$app = JFactory::getApplication();
		$params =& $app->getPageParameters('com_ars');
		$this->assignRef( 'params',		$params );

		parent::display($tpl);
	}

	function onDisplay()
	{
		require_once JPATH_COMPONENT.DS.'helpers'.DS.'html.php';

		// Add RSS links
		$app = JFactory::getApplication();
		$params =& $app->getPageParameters('com_ars');
		$show_feed = $params->get('show_feed_link');
		if($show_feed)
		{
			if ($params->get('show_page_title', 1))
			{
				$title = $params->get('page_title');
			}
			else
			{
				$title = JText::_('ARS_VIEW_BROWSE_TITLE');
			}


			$feed = 'index.php?option=com_ars&view=browse&format=feed';
			$rss = array(
				'type' => 'application/rss+xml',
				'title' => $title.' (RSS)'
			);
			$atom = array(
				'type' => 'application/atom+xml',
				'title' => $title.' (Atom)'
			);
			// add the links
			$document =& JFactory::getDocument();
			$document->addHeadLink(JRoute::_($feed.'&type=rss'), 'alternate',
				'rel', $rss);
			$document->addHeadLink(JRoute::_($feed.'&type=atom'), 'alternate',
				'rel', $atom);
		}
	}
}
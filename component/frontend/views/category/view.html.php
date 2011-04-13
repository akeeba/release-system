<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once( dirname(__FILE__).DS.'..'.DS.'view.html.php' );

class ArsViewCategory extends ArsViewBase
{
	function onDisplay()
	{
		// Add a breadcrumb if necessary
		$model = $this->getModel();
		$repoType = $model->item->type;

		require_once JPATH_COMPONENT.DS.'helpers'.DS.'breadcrumbs.php';
		ArsHelperBreadcrumbs::addRepositoryRoot($repoType);
		ArsHelperBreadcrumbs::addCategory($model->item->id, $model->item->title);

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
				$title = JText::_('ARS_VIEW_CATEGORY_TITLE');
			}


			$feed = 'index.php?option=com_ars&view=category&id='.$model->item->id.'&format=feed';
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
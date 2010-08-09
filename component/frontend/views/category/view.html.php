<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.view');

class ArsViewCategory extends JView
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
		// Add a breadcrumb if necessary
		$model = $this->getModel();
		$repoType = $model->item->type;

		require_once JPATH_COMPONENT.DS.'helpers'.DS.'breadcrumbs.php';
		ArsHelperBreadcrumbs::addRepositoryRoot($repoType);
		ArsHelperBreadcrumbs::addCategory($model->item->id, $model->item->title);

		require_once JPATH_COMPONENT.DS.'helpers'.DS.'html.php';
	}
}
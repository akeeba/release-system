<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Load framework base classes
jimport('joomla.application.component.controller');

/**
 * The Control Panel controller class
 *
 */
class ArsControllerCpanel extends JController
{
	/**
	 * Cache-enabled backend view to accelerate display of statistics data
	 * @param unknown_type $cachable
	 */
	public function display($cachable = false) {
		$cachable = version_compare(JVERSION,'1.6.0','ge');
		parent::display($cachable);
	}
}
<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.view');

class ArsViewImpjed extends JView
{
	public function  display($tpl = null) {
		$tpl = 'raw';
		parent::display($tpl);
	}
}
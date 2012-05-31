<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/**
 * The Control Panel controller class
 *
 */
class ArsControllerCpanel extends FOFController
{
	public function execute($task) {
		$task = 'browse';
		parent::execute($task);
	}
}
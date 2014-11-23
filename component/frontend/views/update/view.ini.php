<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsViewUpdate extends F0FViewHtml
{
	protected function onIni($tpl = null)
	{
		$this->loadHelper('router');

		$task = $this->input->getCmd('task', '');

		$model = $this->getModel();
		$items = $model->items;
		$published = $model->published;
		$this->items = $items;
		$this->published = $published;

		$this->setLayout($this->getModel()->getState('task'));

		// Set the content type to text/plain
		@header('Content-type: text/plain');

		return true;
	}
}
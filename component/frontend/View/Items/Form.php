<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\View\Items;

defined('_JEXEC') or die;


use FOF30\View\DataView\Form as BaseView;

class Form extends BaseView
{
	protected function onBeforeBrowse()
	{
		parent::onBeforeBrowse();

		$this->pagination->setAdditionalUrlParam('option', 'com_ars');
		$this->pagination->setAdditionalUrlParam('view', 'Items');
		$this->pagination->setAdditionalUrlParam('layout', 'modal');
		$this->pagination->setAdditionalUrlParam('tmpl', 'component');
		$this->pagination->setAdditionalUrlParam('Itemid', '');
	}

}

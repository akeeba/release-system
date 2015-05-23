<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\Releases;
use FOF30\Controller\DataController;

class Release extends DataController
{
	protected function onBeforeApplySave(&$data)
	{
		// When you deselect all items Chosen doesn't return any items in the request :(
		if (!isset($data['groups']))
		{
			$data['groups'] = array();
		}
	}

	protected function onBeforeAdd()
	{
		$this->defaultsForAdd = [
			'category'  => 0,
			'maturity'  => 'stable',
			'access'    => 1,
			'published' => 0,
			'language'  => '*',
		];

		$category = $this->getModel()->getState('category_id', null, 'int');

		if ($category)
		{
			$this->defaultsForAdd['category_id'] = $category;
		}

		if ($maturity = $this->getModel()->getState('maturity', 'stable', 'string'))
		{
			$this->defaultsForAdd['maturity'] = $maturity;
		}

		if ($access = $this->getModel()->getState('access', 1, 'int'))
		{
			$this->defaultsForAdd['access'] = $access;
		}

		if ($published = $this->getModel()->getState('published', 0, 'int'))
		{
			$this->defaultsForAdd['published'] = $published;
		}

		if ($language = $this->getModel()->getState('language', '*', 'string'))
		{
			$this->defaultsForAdd['language'] = $language;
		}
	}
}
<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\Database\DatabaseDriver;

class ArsEnvironmentsField extends ListField
{
	protected $type = 'ArsEnvironments';

	protected function getInput()
	{
		/** @var DatabaseDriver $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select([
				$db->qn('id'),
				$db->qn('title'),
			])->from($db->qn('#__ars_environments'));
		$db->setQuery($query);

		$objectList = $db->loadObjectList() ?? [];

		foreach ($objectList as $o)
		{
			$this->addOption($o->title, [
				'value' => $o->id,
			]);
		}

		return parent::getInput();
	}
}
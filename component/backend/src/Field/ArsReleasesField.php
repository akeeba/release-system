<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\GroupedlistField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseDriver;

class ArsReleasesField extends GroupedlistField
{
	protected $type = 'ArsReleases';

	protected function getInput()
	{
		$catId       = null;
		$catStateKey = $this->element['cat_state_key'];

		if ($catStateKey)
		{
			/** @var CMSApplication $app */
			$app   = Factory::getApplication();
			$catId = $app->getUserState($catStateKey);
		}

		/** @var DatabaseDriver $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select([
				$db->qn('r.id'),
				$db->qn('r.version'),
				$db->qn('c.id', 'cat_id'),
				$db->qn('c.title', 'cat_title'),
			])
			->from($db->qn('#__ars_releases', 'r'))
			->join('LEFT', $db->quoteName('#__ars_categories', 'c'),
				$db->quoteName('c.id') . ' = ' . $db->quoteName('r.category_id')
			);

		if ($catId)
		{
			$query
				->where($db->quoteName('r.category_id') . ' = :catid')
				->bind(':catid', $catId);
		}

		$objectList = $db->setQuery($query)->loadObjectList() ?? [];

		if ($catId)
		{
			// Filtered by category: flat list of releases
			foreach ($objectList as $o)
			{
				$this->addOption($o->version, [
					'value' => $o->id,
				]);
			}
		}
		else
		{
			// Unfiltered by category: list of releases grouped by category
			$temp = [];

			foreach ($objectList as $o)
			{
				$key          = $o->cat_title;
				$temp[$key]   = $temp[$key] ?? [];
				$temp[$key][] = $o;
			}

			foreach ($temp as $label => $options)
			{
				$this->addGroup($label, $options);
			}
		}

		if (in_array('advancedSelect', explode(' ', trim($this->class))))
		{
			HTMLHelper::_('formbehavior.chosen');
		}

		return parent::getInput();
	}

	public function addOption($text, $attributes = [], $target = null)
	{
		$target = $target ?? $this->element;

		if ($text && $target instanceof \SimpleXMLElement)
		{
			$child = $target->addChild('option', $text);

			foreach ($attributes as $name => $value)
			{
				$child->addAttribute($name, $value);
			}
		}

		return $this;
	}

	public function addGroup($label, $options = [])
	{
		if ($label && $this->element instanceof \SimpleXMLElement)
		{
			$child = $this->element->addChild('group');
			$child->addAttribute('label', $label);

			foreach ($options as $option)
			{
				$this->addOption($option->version, [
					'value' => $option->id,
				], $child);
			}
		}

		return $this;
	}

}
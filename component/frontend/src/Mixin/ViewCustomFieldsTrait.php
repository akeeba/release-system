<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Mixin;

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

trait ViewCustomFieldsTrait
{
	public function renderCustomFields(object $item, string $context = 'com_ars.category', int $displayType = 0): string
	{
		$fields = array_filter(
			$item->jcfields ?? [],
			fn(object $field): bool => $field->params->get('display', '2') == $displayType
		);

		if (empty($fields))
		{
			return '';
		}

		return FieldsHelper::render(
			$context,
			'fields.render',
			[
				'item'    => $item,
				'context' => $context,
				'fields'  => $fields,
			]
		);
	}

	protected function preprocessCustomFields(object $item, string $context = 'com_ars.category'): object
	{
		try
		{
			@ob_start();
			$this->triggerPluginEvent('onContentPrepare', [$context, $item, null, 0]);
		}
		catch (\Throwable $e)
		{
			return $item;
		}
		finally
		{
			ob_end_clean();
		}

		// Do I have custom fields?
		if (empty($item->jcfields ?? []))
		{
			return $item;
		}

		// I'm about to filter by language. Do I have a multi-language site?
		if (!Multilanguage::isEnabled())
		{
			return $item;
		}

		$appLangTag = Factory::getApplication()->getLanguage()->getTag();

		$item->jcfields = array_filter(
			$item->jcfields,
			fn(object $field): bool => in_array(
				$field->language,
				['*', $appLangTag]
			)
		);

		return $item;
	}
}
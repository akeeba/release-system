<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\Controller\Mixin;

defined('_JEXEC') or die;

trait PopulateModelState
{
	/**
	 * Populates the model state of a list view from the request.
	 *
	 * This is populateModelState() plus the sort order, which every list endpoint supports in the same way.
	 * Joomla's ApiController::displayList() discards a `list.ordering` which the model does not declare in its
	 * filter_fields, and forces an unrecognised `list.direction` to `asc`, so both are safe to take verbatim.
	 *
	 * @param   array  $stateMapper  Array of arrays. Each internal array is [$requestKey, $stateKey, $filterType]
	 *
	 * @return  void
	 * @since   7.5.1
	 */
	protected function populateListModelState(array $stateMapper): void
	{
		$this->populateModelState(
			array_merge(
				$stateMapper,
				[
					['list_ordering', 'list.ordering', 'string'],
					['list_direction', 'list.direction', 'string'],
				]
			)
		);
	}

	/**
	 * Populates the model state from the request.
	 *
	 * @param   array  $stateMapper  Array of arrays. Each internal array is [$requestKey, $stateKey, $filterType]
	 *
	 * @return  void
	 */
	protected function populateModelState(array $stateMapper): void
	{
		foreach ($stateMapper as $map)
		{
			[$requestKey, $stateKey, $filterType] = $map;

			$value = $this->app->getInput()->get($requestKey, null, $filterType);

			if (is_null($value))
			{
				continue;
			}

			switch ($filterType)
			{
				case 'string':
					$this->modelState->set($stateKey, $value);
					break;

				case 'int':
					if ($value !== '')
					{
						$this->modelState->set($stateKey, $value);
					}
					break;

				case 'array':
					if (is_array($value) && !empty($value))
					{
						$this->modelState->set($stateKey, $value);
					}
					break;
			}
		}
	}

}
<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\Controller\Mixin;

defined('_JEXEC') or die;

trait PopulateModelState
{
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

			$value = $this->app->input->get($requestKey, null, $filterType);

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
					if (!$value !== '')
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
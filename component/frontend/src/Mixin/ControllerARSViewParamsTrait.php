<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Mixin;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Model\ItemsModel;
use Akeeba\Component\ARS\Site\Model\CategoriesModel;
use Akeeba\Component\ARS\Site\Model\ReleasesModel;

trait ControllerARSViewParamsTrait
{
	protected function applyCategoryOrderBy(?string $orderBy, CategoriesModel $model)
	{
		$orderBy = $orderBy ?? 'none';

		switch ($orderBy)
		{
			case 'none':
			default:
				$model->setState('list.ordering', 'id');
				$model->setState('list.direction', 'asc');
				break;

			case 'alpha':
				$model->setState('list.ordering', 'title');
				$model->setState('list.direction', 'asc');
				break;

			case 'ralpha':
				$model->setState('list.ordering', 'title');
				$model->setState('list.direction', 'desc');
				break;

			case 'created':
				$model->setState('list.ordering', 'created');
				$model->setState('list.direction', 'asc');
				break;

			case 'rcreated':
				$model->setState('list.ordering', 'created');
				$model->setState('list.direction', 'desc');
				break;

			case 'order':
				$model->setState('list.ordering', 'ordering');
				$model->setState('list.direction', 'asc');
				break;
		}
	}

	protected function applyReleaseOrderBy(?string $orderBy, ReleasesModel $model)
	{
		$orderBy = $orderBy ?? 'none';

		switch ($orderBy)
		{
			case 'none':
			default:
				$model->setState('list.ordering', 'r.id');
				$model->setState('list.direction', 'asc');
				break;

			case 'alpha':
				$model->setState('list.ordering', 'r.version');
				$model->setState('list.direction', 'asc');
				break;

			case 'ralpha':
				$model->setState('list.ordering', 'r.version');
				$model->setState('list.direction', 'desc');
				break;

			case 'created':
				$model->setState('list.ordering', 'r.created');
				$model->setState('list.direction', 'asc');
				break;

			case 'rcreated':
				$model->setState('list.ordering', 'r.created');
				$model->setState('list.direction', 'desc');
				break;

			case 'order':
				$model->setState('list.ordering', 'r.ordering');
				$model->setState('list.direction', 'asc');
				break;
		}
	}

	protected function applyItemsOrderBy(?string $orderBy, ItemsModel $model)
	{
		$orderBy = $orderBy ?? 'none';

		switch ($orderBy)
		{
			case 'none':
			default:
				$model->setState('list.ordering', 'i.id');
				$model->setState('list.direction', 'asc');
				break;

			case 'alpha':
				$model->setState('list.ordering', 'i.title');
				$model->setState('list.direction', 'asc');
				break;

			case 'ralpha':
				$model->setState('list.ordering', 'i.version');
				$model->setState('list.direction', 'desc');
				break;

			case 'created':
				$model->setState('list.ordering', 'i.created');
				$model->setState('list.direction', 'asc');
				break;

			case 'rcreated':
				$model->setState('list.ordering', 'i.created');
				$model->setState('list.direction', 'desc');
				break;

			case 'order':
				$model->setState('list.ordering', 'i.ordering');
				$model->setState('list.direction', 'asc');
				break;
		}
	}
}
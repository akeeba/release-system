<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

use Akeeba\ReleaseSystem\Admin\Model\Environments;
use Akeeba\ReleaseSystem\Admin\Model\Items;
use Akeeba\ReleaseSystem\Admin\Model\Releases;
use FOF30\Container\Container;
use JHtml;

defined('_JEXEC') or die;

abstract class Select
{
	protected static function genericlist($list, $name, $attribs, $selected, $idTag)
	{
		if (empty($attribs))
		{
			$attribs = null;
		}
		else
		{
			$temp = '';

			foreach ($attribs as $key => $value)
			{
				$temp .= $key . ' = "' . $value . '"';
			}

			$attribs = $temp;
		}

		return JHTML::_('select.genericlist', $list, $name, $attribs, 'value', 'text', $selected, $idTag);
	}

	public static function environmentIcon($id, $attribs = array())
	{
		static $items = null;

		if (is_null($items))
		{
			/** @var Environments $environmentsModel */
			$environmentsModel = Container::getInstance('com_ars')->factory->model('Environments')->tmpInstance();
			// We use getItemsArray instead of get to fetch an associative array
			$items = $environmentsModel->getItemsArray(0, 0, true);
		}

		if (!isset($items[ $id ]))
		{
			return '';
		}

		$base_folder = rtrim(\JUri::base(), '/');

		if (substr($base_folder, - 13) == 'administrator')
		{
			$base_folder = rtrim(substr($base_folder, 0, - 13), '/');
		}

		return \JHtml::image($base_folder . '/media/com_ars/environments/' . $items[ $id ]->icon, $items[ $id ]->title, $attribs);
	}

	public static function releases($selected = null, $id = 'release', $attribs = array(), $category_id = null)
	{
		$container = Container::getInstance('com_ars');

		/** @var Releases $model */
		$model = $container->factory->model('Releases')->tmpInstance();

		if (!empty($category_id))
		{
			$model->setState('category', $category_id);
		}

		if (empty($category_id))
		{
			$model->setState('nobeunpub', 1);
		}

		$items = $model->get(true);

		$options = array();

		if (!$items->count())
		{
			return $options;
		}

		if (empty($category_id))
		{
			$cache = array();

			/** @var Releases $item */
			foreach ($items as $item)
			{
				if (!array_key_exists($item->category->title, $cache))
				{
					$cache[ $item->category->title ] = array();
				}

				$cache[ $item->category->title ][] = (object) array('id' => $item->id, 'version' => $item->version);
			}

			foreach ($cache as $category => $releases)
			{
				if (!empty($options))
				{
					$options[] = JHtml::_('select.option', '</OPTGROUP>');
				}

				$options[] = JHtml::_('select.option', '<OPTGROUP>', $category);

				foreach ($releases as $release)
				{
					$options[] = JHtml::_('select.option', $release->id, $release->version);
				}
			}
		}
		else
		{
			/** @var Releases $item */
			foreach ($items as $item)
			{
				if ($item->category_id == $category_id)
				{
					$options[] = JHtml::_('select.option', $item->id, $item->version);
				}
			}
		}

		array_unshift($options, JHtml::_('select.option', 0, '- ' . \JText::_('COM_ARS_COMMON_SELECT_RELEASE_LABEL') . ' -'));

		return $options;
	}

	public static function getFiles($selected = null, $release_id = 0, $item_id = 0, $id = 'type', $attribs = array())
	{
		$container = Container::getInstance('com_ars');

		/** @var Items $model */
		$model = $container->factory->model('Items')->tmpInstance();

		$options = $model->getFilesOptions($release_id, $item_id);

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}
}
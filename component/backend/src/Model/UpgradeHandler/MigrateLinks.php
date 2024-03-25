<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model\UpgradeHandler;

use Akeeba\Component\ARS\Administrator\Model\UpgradeModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

class MigrateLinks implements DatabaseAwareInterface
{
	use DatabaseAwareTrait;

	/**
	 * The UpgradeModel instance we belong to.
	 *
	 * @var   UpgradeModel
	 */
	private $upgradeModel;

	/**
	 * Constructor.
	 *
	 * @param   UpgradeModel  $upgradeModel  The UpgradeModel instance we belong to
	 */
	public function __construct(UpgradeModel $upgradeModel, DatabaseDriver $dbo)
	{
		$this->upgradeModel = $upgradeModel;
		$this->setDatabase($dbo);
	}

	public function onUpdate($type, $parent)
	{
		$this->onMigrateLinks();
	}

	/**
	 * Update existing menu links
	 *
	 * In previous versions the item ID of menu items for releases, items and update feeds was not part of the link but
	 * included in the params json. This caused issues with SEF routing where multiple items on the same page received
	 * the same link because all menu links appeared to be identical. This function moves the item ID from the params
	 * json to the link in #__menu table.
	 *
	 * @return  void
	 */
	public function onMigrateLinks(): void
	{
		$db = $this->getDatabase();

		// get affected menu items
		$query     = $db->getQuery(true)
			->select($db->qn(['m.id', 'm.link', 'm.params']))
			->from($db->qn('#__menu') . ' AS m')
			->join(
				'INNER',
				$db->qn('#__extensions') . ' AS e ON (' . $db->qn('e.extension_id') . ' = ' . $db->qn('m.component_id')
				. ')'
			)
			->where($db->qn('m.client_id') . ' = ' . $db->q(0))
			->where($db->qn('e.type') . ' = ' . $db->q('component'))
			->where($db->qn('e.element') . ' = ' . $db->q('com_ars'));
		$menuItems = $db->setQuery($query)->loadObjectList();

		if (empty($menuItems))
		{
			return;
		}

		foreach ($menuItems as $menuItem)
		{
			// Load Uri object and parse link
			$uri = new Uri;
			$uri->parse($menuItem->link);

			// Only process views, which link to a particular item
			if (!in_array($uri->getVar('view'), ['releases', 'items', 'update']))
			{
				continue;
			}

			$params = json_decode($menuItem->params, true);

			// Move item ID from params to link
			switch ($uri->getVar('view'))
			{
				case 'releases':
					if ($uri->getVar('category_id') || empty($params['category_id']))
					{
						continue 2;
					}

					$uri->setVar('category_id', $params['category_id']);

					unset($params['category_id']);

					break;

				case 'items':
					if ($uri->getVar('release_id') || empty($params['release_id']))
					{
						continue 2;
					}

					$uri->setVar('release_id', $params['release_id']);

					unset($params['release_id']);

					break;

				case 'update':
					switch ($uri->getVar('layout'))
					{
						case 'ini':
						case 'stream':
							if ($uri->getVar('stream_id') || empty($params['stream_id']))
							{
								continue 3;
							}

							$uri->setVar('stream_id', $params['stream_id']);

							unset($params['stream_id']);

							break;

						case 'category':
							if ($uri->getVar('category') || empty($params['category']))
							{
								continue 3;
							}

							$uri->setVar('category', $params['category']);

							unset($params['category']);

							break;

						default:
							continue 3;
					}

					break;
			}

			// write updated data back to menu table
			$query = $db->getQuery(true)
				->update($db->qn('#__menu'))
				->set($db->qn('link') . ' = ' . $db->q($uri->toString()))
				->set($db->qn('params') . ' = ' . $db->q(json_encode($params)))
				->where($db->qn('id') . ' = ' . $db->q($menuItem->id));
			$db->setQuery($query)->execute();
		}
	}
}
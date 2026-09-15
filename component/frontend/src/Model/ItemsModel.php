<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Model;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Administrator\Model\ItemsModel as AdminItemsModel;
use Joomla\CMS\Form\Form;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

#[\AllowDynamicProperties]
class ItemsModel extends AdminItemsModel
{
	protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = null)
	{
		$backendPath = JPATH_ADMINISTRATOR . '/components/com_ars';

		Form::addFormPath($backendPath . '/forms');
		Form::addFormPath($backendPath . '/models/forms');
		Form::addFieldPath($backendPath . '/models/fields');
		Form::addFormPath($backendPath . '/model/form');
		Form::addFieldPath($backendPath . '/model/field');

		return parent::loadForm($name, $source, $options, $clear, $xpath);
	}

	/**
	 * Confines every frontend item listing to PUBLISHED, access-authorised parent releases and
	 * categories, not just the item row itself.
	 *
	 * The normal frontend browse ({@see \Akeeba\Component\ARS\Site\Controller\ItemsController::onBeforeDisplay()})
	 * always pre-authorises a single release/category via `accessControlRelease()`/`accessControlCategory()`
	 * before ever reaching this query, so this is redundant-but-harmless there. The item-picker MODAL used
	 * by the `arslink` editor button ({@see \Akeeba\Component\ARS\Site\Controller\ItemsController::onBeforeBrowseModal()})
	 * has no such per-record gate — it queries across every release and category with only the item's own
	 * `published`/`access` filtered — so without this override, it would list items belonging to
	 * unpublished or access-restricted releases/categories to anyone who can reach the modal, including an
	 * unauthenticated visitor who constructs the URL directly.
	 *
	 * @return  \Joomla\Database\QueryInterface
	 * @since   7.5.1
	 */
	protected function getListQuery()
	{
		$query = parent::getListQuery();

		$db = $this->getDatabase();

		$query->where($db->quoteName('r.published') . ' = 1')
			->where($db->quoteName('c.published') . ' = 1');

		$access = $this->getState('filter.access');

		if (is_numeric($access))
		{
			$query->where($db->quoteName('r.access') . ' = :relAccess')
				->where($db->quoteName('c.access') . ' = :catAccess')
				->bind(':relAccess', $access, ParameterType::INTEGER)
				->bind(':catAccess', $access, ParameterType::INTEGER);
		}
		elseif (is_array($access) && !empty($access))
		{
			$access = ArrayHelper::toInteger($access);

			$query->whereIn($db->quoteName('r.access'), $access)
				->whereIn($db->quoteName('c.access'), $access);
		}

		return $query;
	}
}
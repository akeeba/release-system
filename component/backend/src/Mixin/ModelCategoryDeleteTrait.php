<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') or die();

use Joomla\CMS\Factory;

/**
 * Delete permission for records which belong to an ARS category.
 *
 * @since  7.5.3
 */
trait ModelCategoryDeleteTrait
{
	/**
	 * May the current user delete a record which lives in the given category?
	 *
	 * Checks core.delete on the category's asset only. The category asset inherits the privilege from the component,
	 * so a grant on the component or on the category alone is enough, while an explicit Deny on the category always
	 * wins over an Allow on the component. Never fall back to the component asset: that would ignore the Deny.
	 *
	 * @param   int|null  $categoryId  The category the record belongs to; NULL or 0 checks the component instead.
	 *
	 * @return  bool
	 * @since   7.5.3
	 */
	protected function canDeleteInCategory(?int $categoryId): bool
	{
		$user = Factory::getApplication()->getIdentity();

		if (!$user)
		{
			return false;
		}

		$asset = empty($categoryId) ? 'com_ars' : ('com_ars.category.' . $categoryId);

		return (bool) $user->authorise('core.delete', $asset);
	}
}

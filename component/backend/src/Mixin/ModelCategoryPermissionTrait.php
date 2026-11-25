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
 * Delete and publish / unpublish permissions for records which belong to an ARS category.
 *
 * Both check the privilege on the category's asset only. The category asset inherits the privilege from the component,
 * so a grant on the component or on the category alone is enough, while an explicit Deny on the category always wins
 * over an Allow on the component. Never fall back to the component asset: that would ignore the Deny.
 *
 * @since  7.5.3
 */
trait ModelCategoryPermissionTrait
{
	/**
	 * May the current user delete a record which lives in the given category?
	 *
	 * @param   int|null  $categoryId  The category the record belongs to; NULL or 0 checks the component instead.
	 *
	 * @return  bool
	 * @since   7.5.3
	 */
	protected function canDeleteInCategory(?int $categoryId): bool
	{
		return $this->isAuthorisedInCategory('core.delete', $categoryId);
	}

	/**
	 * May the current user publish, unpublish, archive or trash a record which lives in the given category?
	 *
	 * @param   int|null  $categoryId  The category the record belongs to; NULL or 0 checks the component instead.
	 *
	 * @return  bool
	 * @since   7.5.3
	 */
	protected function canEditStateInCategory(?int $categoryId): bool
	{
		return $this->isAuthorisedInCategory('core.edit.state', $categoryId);
	}

	/**
	 * Check a privilege on the category's asset, or on the component's when there is no category.
	 *
	 * @param   string    $action      The privilege, e.g. core.delete.
	 * @param   int|null  $categoryId  The category ID; NULL or 0 for the component.
	 *
	 * @return  bool
	 * @since   7.5.3
	 */
	private function isAuthorisedInCategory(string $action, ?int $categoryId): bool
	{
		$user = Factory::getApplication()->getIdentity();

		if (!$user)
		{
			return false;
		}

		$asset = empty($categoryId) ? 'com_ars' : ('com_ars.category.' . $categoryId);

		return (bool) $user->authorise($action, $asset);
	}
}

<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller\Mixin;

use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Akeeba\Component\ARS\Administrator\Table\ItemTable;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use RuntimeException;

/**
 * Category, Release and Item access control trait
 */
trait CRIAccessAware
{
	/**
	 * Makes sure the category exists and the user has access to it
	 *
	 * @param   int  $category_id  The category ID to check
	 *
	 * @return  CategoryTable  The loaded category table object
	 * @throws  Exception
	 */
	protected function accessControlCategory(int $category_id, bool $redirectImmediately = true): ?CategoryTable
	{
		return $this->accessControlFor('Category', $category_id, $redirectImmediately);
	}

	/**
	 * Makes sure the release exists and the user has access to it
	 *
	 * @param   int  $release_id  The release ID to check
	 *
	 * @return  ReleaseTable  The loaded release table object
	 * @throws  Exception
	 */
	protected function accessControlRelease(int $release_id, bool $redirectImmediately = true): ?ReleaseTable
	{
		return $this->accessControlFor('Release', $release_id, $redirectImmediately);
	}

	/**
	 * Makes sure the item exists and the user has access to it
	 *
	 * @param   int  $item_id  The release ID to check
	 *
	 * @return  ItemTable  The loaded item table object
	 * @throws  Exception
	 */
	protected function accessControlItem(int $item_id, bool $redirectImmediately = true): ?ItemTable
	{
		return $this->accessControlFor('Item', $item_id, $redirectImmediately);
	}

	/**
	 * Makes sure the category, release or item exists and the user has access to it
	 *
	 * @param   int  $primaryKey  The category ID to check
	 *
	 * @return  CategoryTable|ReleaseTable|ItemTable  The loaded table object
	 * @throws  Exception
	 */
	private function accessControlFor(string $tableType, int $primaryKey, bool $redirectImmediately = true)
	{
		// Does the record exist?
		/** @var CategoryTable|ReleaseTable|ItemTable $object */
		$object = $this->getModel($tableType, 'Administrator')->getTable();

		$notFoundKey = sprintf('COM_ARS_%s_ERR_NOT_FOUND', $tableType);

		if (!$object->load($primaryKey))
		{
			throw new RuntimeException(Text::_($notFoundKey), 404);
		}

		// Is the record unpublished?
		if ($object->published != 1)
		{
			throw new RuntimeException(Text::_($notFoundKey), 404);
		}

		// Is the record in the correct language?
		if (Multilanguage::isEnabled() && !in_array($object->language, ['*', $this->app->getLanguage()->getTag()]))
		{
			throw new RuntimeException(Text::_($notFoundKey), 404);
		}

		// Does the user have access to the record?
		if (in_array($object->access, $this->app->getIdentity()->getAuthorisedViewLevels()))
		{
			// Access granted, no need for further action.
			return $object;
		}

		// Am I supposed to redirect the user?
		if (!$object->show_unauth_links)
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$params      = $this->app->getParams();
		$redirectUrl = ($object->redirect_unauth ?: $params->get('no_access_url', null)) ?: 'index.php';

		// Do I need to route the redirection URL?
		if ((substr($redirectUrl, 0, 7) !== 'http://') && (substr($redirectUrl, 0, 7) !== 'https://'))
		{
			$redirectUrl = Route::_($redirectUrl);
		}

		// Invalid redirection URL. I will show an error instead.
		if (empty($redirectUrl))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->setRedirect($redirectUrl);

		if ($redirectImmediately)
		{
			$this->redirect();
		}
		else
		{
			return null;
		}

		// This line never executes. It's only here to appease static code analysers.
		return $object;
	}

}
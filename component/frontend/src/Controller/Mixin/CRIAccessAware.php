<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller\Mixin;

use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
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
	 * Makes sure the category exists
	 *
	 * @param   int  $category_id  The category ID to check
	 *
	 * @return  CategoryTable  The loaded category table object
	 * @throws  Exception
	 */
	protected function accessControlCategory(int $category_id): CategoryTable
	{
		// Does the category exist?
		/** @var CategoryTable $category */
		$category = $this->getModel('Category', 'Administrator')->getTable();

		if (!$category->load($category_id))
		{
			throw new RuntimeException(Text::_('COM_ARS_CATEGORY_ERR_NOT_FOUND'), 404);
		}

		// Is the category unpublished?
		if ($category->published != 1)
		{
			throw new RuntimeException(Text::_('COM_ARS_CATEGORY_ERR_NOT_FOUND'), 404);
		}

		// Is the category the correct language?
		if (Multilanguage::isEnabled() && !in_array($category->language, ['*', $this->app->getLanguage()->getTag()]))
		{
			throw new RuntimeException(Text::_('COM_ARS_CATEGORY_ERR_NOT_FOUND'), 404);
		}

		// Does the user have access to the category?
		if (in_array($category->access, ($this->app->getIdentity() ?: Factory::getUser())->getAuthorisedViewLevels()))
		{
			// Access granted, no need for further action.
			return $category;
		}

		// Am I supposed to redirect the user?
		if (!$category->show_unauth_links)
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$params      = $this->app->getParams();
		$redirectUrl = ($category->redirect_unauth ?: $params->get('no_access_url', null)) ?: 'index.php';

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
		$this->redirect();

		// This line never executes. It's only here to appease static code analysers.
		return $category;
	}

}
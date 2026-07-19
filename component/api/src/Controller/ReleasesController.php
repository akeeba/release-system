<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Api\Controller\Mixin\AssertApiAccess;
use Akeeba\Component\ARS\Api\Controller\Mixin\PopulateModelState;
use Joomla\CMS\MVC\Controller\ApiController;

class ReleasesController extends ApiController
{
	use PopulateModelState;
	use AssertApiAccess;

	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  7.0.0
	 */
	protected $contentType = 'releases';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  7.0.0
	 */
	protected $default_view = 'releases';

	public function displayList()
	{
		$this->assertCanManage();

		$stateMapper = [
			['search', 'filter.search', 'string'],
			['category_id', 'filter.category_id', 'int'],
			['published', 'filter.published', 'int'],
			['maturity', 'filter.maturity', 'string'],
			['minMaturity', 'filter.minMaturity', 'string'],
			['show_unauth_links', 'filter.show_unauth_links', 'int'],
			// Yes, access is here twice. INT if I am passing a single access level, ARRAY if I'm passing multiple
			['access', 'filter.access', 'int'],
			['access', 'filter.access', 'array'],
			['language', 'filter.language', 'string'],
			['latest', 'filter.latest', 'int'],
		];

		$this->populateModelState($stateMapper);

		return parent::displayList();
	}

	public function displayItem($id = null)
	{
		$this->assertCanManage();

		return parent::displayItem($id);
	}

	public function delete($id = null)
	{
		$this->assertCanManage();

		return parent::delete($id);
	}

	protected function allowAdd($data = [])
	{
		$user = $this->app->getIdentity();

		if (!$user->authorise('core.manage', 'com_ars')) {
			return false;
		}

		if (empty($data)) {
			$data = $this->getRequestData();
		}

		// A release always belongs to a category. Check the category permissions.
		$categoryId = (int) ($data['category_id'] ?? 0);

		if (!$categoryId) {
			return false;
		}

		return $user->authorise('core.create', 'com_ars.category.' . $categoryId);
	}

	protected function allowEdit($data = [], $key = 'id')
	{
		$user = $this->app->getIdentity();

		if (!$user->authorise('core.manage', 'com_ars')) {
			return false;
		}

		$recordId = (int) ($data[$key] ?? 0);

		if (!$recordId) {
			return false;
		}

		$release    = $this->getModel('Release')->getItem($recordId);
		$categoryId = $release ? (int) ($release->category_id ?? 0) : 0;

		if (!$categoryId) {
			return false;
		}

		return $user->authorise('core.edit', 'com_ars.category.' . $categoryId);
	}
}

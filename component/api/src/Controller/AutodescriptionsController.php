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

/**
 * JSON:API controller for the Automatic Item Descriptions.
 *
 * @since  7.5.1
 */
class AutodescriptionsController extends ApiController
{
	use PopulateModelState;
	use AssertApiAccess;

	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  7.5.1
	 */
	protected $contentType = 'autodescriptions';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  7.5.1
	 */
	protected $default_view = 'autodescriptions';

	public function displayList()
	{
		$this->assertCanManage();

		$stateMapper = [
			['search', 'filter.search', 'string'],
			['id', 'filter.id', 'int'],
			['category_id', 'filter.category_id', 'int'],
			['title', 'filter.title', 'string'],
			['packname', 'filter.packname', 'string'],
			['environment_id', 'filter.environment_id', 'int'],
			['created_by', 'filter.created_by', 'int'],
			['show_unauth_links', 'filter.show_unauth_links', 'int'],
			// Yes, access is here twice. INT if I am passing a single access level, ARRAY if I'm passing multiple
			['access', 'filter.access', 'int'],
			['access', 'filter.access', 'array'],
			['published', 'filter.published', 'int'],
		];

		$this->populateListModelState($stateMapper);

		return parent::displayList();
	}

	public function displayItem($id = null)
	{
		$this->assertCanManage();

		return parent::displayItem($id);
	}

	public function delete($id = null)
	{
		if ($id === null) {
			$id = $this->input->get('id', 0, 'int');
		}

		$this->assertCanDelete($this->getCategoryId((int) $id));

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

		// An automatic item description always belongs to a category. Check the category permissions.
		$categoryId = (int) ($data['category'] ?? $data['category_id'] ?? $data['catid'] ?? 0);

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

		$categoryId = $this->getCategoryId($recordId);

		if (!$categoryId) {
			return false;
		}

		return $user->authorise('core.edit', 'com_ars.category.' . $categoryId);
	}

	/**
	 * Get the ID of the category an automatic item description belongs to.
	 *
	 * @param   int  $recordId  The automatic item description ID.
	 *
	 * @return  int  The category ID, 0 if it cannot be determined.
	 * @since   7.5.1
	 */
	private function getCategoryId(int $recordId): int
	{
		if (!$recordId) {
			return 0;
		}

		$record = $this->getModel('Autodescription')->getItem($recordId);

		return $record ? (int) ($record->category ?? 0) : 0;
	}
}

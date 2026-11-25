<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Api\Controller\Mixin\AssertApiAccess;
use Akeeba\Component\ARS\Api\Controller\Mixin\DeleteRecords;
use Akeeba\Component\ARS\Api\Controller\Mixin\PopulateModelState;
use Joomla\CMS\MVC\Controller\ApiController;

/**
 * JSON:API controller for the Environments.
 *
 * Environments do not belong to a category — they are a flat, component-wide vocabulary — so, unlike releases,
 * items, update streams and automatic item descriptions, they are authorised at the component level only.
 *
 * @since  7.5.1
 */
class EnvironmentsController extends ApiController
{
	use PopulateModelState;
	use AssertApiAccess;
	use DeleteRecords;

	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  7.5.1
	 */
	protected $contentType = 'environments';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  7.5.1
	 */
	protected $default_view = 'environments';

	public function displayList()
	{
		$this->assertCanManage();

		$stateMapper = [
			['search', 'filter.search', 'string'],
			['id', 'filter.id', 'int'],
			['title', 'filter.title', 'string'],
			['xmltitle', 'filter.xmltitle', 'string'],
			['platform', 'filter.platform', 'string'],
			['created_by', 'filter.created_by', 'int'],
		];

		$this->populateListModelState($stateMapper);

		return parent::displayList();
	}

	public function displayItem($id = null)
	{
		$this->assertCanManage();

		return parent::displayItem($id);
	}

	protected function allowDelete($data = [], $key = 'id'): bool
	{
		$recordId    = (int) ($data[$key] ?? 0);
		$environment = $recordId ? $this->getModel('Environment')->getItem($recordId) : null;

		if (empty($environment->id)) {
			return false;
		}

		// Environments are component-wide.
		return $this->canDeleteIn();
	}

	protected function allowAdd($data = [])
	{
		$user = $this->app->getIdentity();

		if (!$user->authorise('core.manage', 'com_ars')) {
			return false;
		}

		return $user->authorise('core.create', 'com_ars');
	}

	protected function allowEdit($data = [], $key = 'id')
	{
		$user = $this->app->getIdentity();

		if (!$user->authorise('core.manage', 'com_ars')) {
			return false;
		}

		if (!((int) ($data[$key] ?? 0))) {
			return false;
		}

		return $user->authorise('core.edit', 'com_ars');
	}
}

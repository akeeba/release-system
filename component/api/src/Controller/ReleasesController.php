<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Api\Controller\Mixin\PopulateModelState;
use Joomla\CMS\MVC\Controller\ApiController;

class ReleasesController extends ApiController
{
	use PopulateModelState;

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
}
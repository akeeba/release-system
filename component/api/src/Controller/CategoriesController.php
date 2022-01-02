<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Api\Controller\Mixin\PopulateModelState;
use Joomla\CMS\MVC\Controller\ApiController;

class CategoriesController extends ApiController
{
	use PopulateModelState;

	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  7.0.0
	 */
	protected $contentType = 'categories';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  7.0.0
	 */
	protected $default_view = 'categories';

	public function displayList()
	{
		$stateMapper = [
			['search', 'filter.search', 'string'],
			['published', 'filter.published', 'int'],
			['show_unauth_links', 'filter.show_unauth_links', 'int'],
			['supported', 'filter.supported', 'int'],
			['access', 'filter.access', 'int'],
			['language', 'filter.language', 'string'],
		];

		$this->populateModelState($stateMapper);

		return parent::displayList();
	}
}
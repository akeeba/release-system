<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\Controller;

defined('_JEXEC') || die;

use Joomla\CMS\MVC\Controller\ApiController;

class ItemsController extends ApiController
{
	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  7.0.0
	 */
	protected $contentType = 'items';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  7.0.0
	 */
	protected $default_view = 'items';
}
<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\View\Updatestreams;

defined('_JEXEC') || die;

use Joomla\CMS\MVC\View\JsonApiView as BaseJsonApiView;

class JsonapiView extends BaseJsonApiView
{
	/**
	 * The fields to render for single item display tasks
	 *
	 * @var    array
	 * @since  7.5.1
	 */
	protected $fieldsToRenderItem = [
		'id',
		'name',
		'alias',
		'type',
		'element',
		'category',
		'packname',
		'client_id',
		'folder',
		'created',
		'created_by',
		'modified',
		'modified_by',
		'checked_out',
		'checked_out_time',
		'published',
		'cat_title',
		'cat_alias',
		'cat_type',
	];

	/**
	 * The fields to render for multiple items display tasks
	 *
	 * @var   array
	 * @since 7.5.1
	 */
	protected $fieldsToRenderList = [
		'id',
		'name',
		'alias',
		'type',
		'element',
		'category',
		'packname',
		'client_id',
		'folder',
		'created',
		'created_by',
		'modified',
		'modified_by',
		'checked_out',
		'checked_out_time',
		'published',
		'cat_title',
		'cat_alias',
		'cat_type',
	];
}

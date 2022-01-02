<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\View\Categories;

defined('_JEXEC') || die;

use Joomla\CMS\MVC\View\JsonApiView as BaseJsonApiView;

class JsonapiView extends BaseJsonApiView
{
	/**
	 * The fields to render for single item display tasks
	 *
	 * @var    array
	 * @since  7.0.0
	 */
	protected $fieldsToRenderItem = [
		'id',
		'asset_id',
		'title',
		'alias',
		'description',
		'type',
		'directory',
		'created',
		'created_by',
		'modified',
		'modified_by',
		'checked_out',
		'checked_out_time',
		'ordering',
		'access',
		'show_unauth_links',
		'redirect_unauth',
		'published',
		'is_supported',
		'language',
	];

	/**
	 * The fields to render for multiple items display tasks
	 *
	 * @var   array
	 * @since 7.0.0
	 */
	protected $fieldsToRenderList = [
		'id',
		'asset_id',
		'title',
		'alias',
		'description',
		'type',
		'directory',
		'created',
		'created_by',
		'modified',
		'modified_by',
		'checked_out',
		'checked_out_time',
		'ordering',
		'access',
		'show_unauth_links',
		'redirect_unauth',
		'published',
		'is_supported',
		'language',
	];
}
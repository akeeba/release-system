<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\View\Items;

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
		'release_id',
		'title',
		'alias',
		'description',
		'type',
		'filename',
		'url',
		'updatestream',
		'md5',
		'sha1',
		'sha256',
		'sha384',
		'sha512',
		'filesize',
		'hits',
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
		'language',
		'environments',
	];

	/**
	 * The fields to render for multiple items display tasks
	 *
	 * @var   array
	 * @since 7.0.0
	 */
	protected $fieldsToRenderList = [
		'id',
		'release_id',
		'title',
		'alias',
		'description',
		'type',
		'filename',
		'url',
		'updatestream',
		'md5',
		'sha1',
		'sha256',
		'sha384',
		'sha512',
		'filesize',
		'hits',
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
		'language',
		'environments',
	];
}
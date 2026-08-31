<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\View\Dlidlabels;

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
		'user_id',
		'primary',
		'title',
		'dlid',
		'published',
		'created',
		'created_by',
		'modified',
		'modified_by',
		'checked_out',
		'checked_out_time',
		'name',
		'username',
		'email',
	];

	/**
	 * The fields to render for multiple items display tasks
	 *
	 * @var   array
	 * @since 7.5.1
	 */
	protected $fieldsToRenderList = [
		'id',
		'user_id',
		'primary',
		'title',
		'dlid',
		'published',
		'created',
		'created_by',
		'modified',
		'modified_by',
		'checked_out',
		'checked_out_time',
		'name',
		'username',
		'email',
	];
}

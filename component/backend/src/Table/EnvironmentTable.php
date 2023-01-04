<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\TableAssertionTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableColumnAliasTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableCreateModifyTrait;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;

/**
 * ARS Environments table
 *
 * @property int $id               Primary key
 * @property int $title            Title, for management
 * @property int $xmltitle         XML title, e.g. platform/1.2
 * @property int $created          Created date
 * @property int $created_by       User ID which created this record
 * @property int $modified         Last modified date
 * @property int $modified_by      User ID which last modified this record
 * @property int $checked_out      User ID which checked out this record
 * @property int $checked_out_time Checked out date
 */
class EnvironmentTable extends AbstractTable
{
	use TableCreateModifyTrait;
	use TableAssertionTrait;
	use TableColumnAliasTrait;

	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $_supportNullValue = false;

	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__ars_environments', ['id'], $db);

		$this->created_by = Factory::getApplication()->getIdentity()->id;
		$this->created    = Factory::getDate()->toSql();
	}

	protected function onBeforeCheck()
	{
		$this->assertNotEmpty($this->title, 'COM_ARS_ENVIRONMENT_ERR_NEEDS_TITLE');
		$this->assertNotEmpty($this->xmltitle, 'COM_ARS_ENVIRONMENT_ERR_NEEDS_XMLTITLE');

		// Validate the XML title
		$this->assert(strpos($this->xmltitle, '/') !== false, 'COM_ARS_ENVIRONMENT_ERR_NEEDS_XMLTITLE_VALID');

		[$platform, $version] = explode('/', $this->xmltitle);
		$this->assertNotEmpty($platform, 'COM_ARS_ENVIRONMENT_ERR_NEEDS_XMLTITLE_VALID');

		if (!empty($version))
		{
			$this->assert(preg_match('/^(\d+\.){0,}\d+$/', $version), 'COM_ARS_ENVIRONMENT_ERR_NEEDS_XMLTITLE_VALID');
		}
	}
}
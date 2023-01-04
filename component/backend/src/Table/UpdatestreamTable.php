<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table;


use Akeeba\Component\ARS\Administrator\Mixin\TableAssertionTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableColumnAliasTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableCreateModifyTrait;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

/**
 * ARS Update Stream
 *
 * @property int    $id                Primary key
 * @property string $name              Update stream title
 * @property string $alias             Alias for URLs
 * @property string $type              Stream type: 'components','libraries','modules','packages',
 * 'plugins','files', or'templates'
 * @property string $element           Joomla extension short name
 * @property int    $category          ARS category where this update stream applies to
 * @property string $pockname          fnmatch pattern for the files to be matched by this stream
 * @property int    $client_id         Joomla client ID
 * @property string $folder            Folder, for plugins
 * @property string $created           Created date and time
 * @property int    $created_by        Created by this user
 * @property string $modified          Modified date and time
 * @property int    $modified_by       Modified by this user
 * @property int    $checked_out       Checked out by this user
 * @property string $checked_out_time  Checked out date and time
 * @property int    $published         Publish state
 *
 */
class UpdatestreamTable extends AbstractTable
{
	use TableCreateModifyTrait;
	use TableAssertionTrait;
	use TableColumnAliasTrait;

	public function __construct(DatabaseDriver $db, DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__ars_updatestreams', 'id', $db, $dispatcher);

		$this->setColumnAlias('title', 'name');

		$this->created_by = Factory::getApplication()->getIdentity()->id;
		$this->created    = Factory::getDate()->toSql();
	}

	protected function onBeforeCheck()
	{
		$this->assertNotEmpty($this->name, 'COM_ARS_UPDATESTREAM_ERR_NEEDS_NAME');

		$this->alias = $this->alias ?: ApplicationHelper::stringURLSafe($this->name);
		$this->assertNotEmpty($this->alias, 'COM_ARS_UPDATESTREAM_ERR_NEEDS_ALIAS');

		// Check alias for uniqueness
		$db    = $this->getDBO();
		$query = $db->getQuery(true)
			->select($db->qn('alias'))
			->from($db->qn('#__ars_updatestreams'));

		if ($this->id)
		{
			$query->where($db->qn('id') . ' != :id')
				->bind(':id', $this->id);
		}

		$aliases       = $db->setQuery($query)->loadColumn() ?: [];
		$numericSuffix = 0;
		$alias         = $this->alias;

		while (in_array($alias, $aliases) && ($numericSuffix < 100))
		{
			$alias = $this->alias . '-' . ++$numericSuffix;
		}

		$this->alias = $alias;
		$this->assertNotInArray($this->alias, $aliases, 'COM_ARS_UPDATESTREAM_ERR_NEEDS_UNIQUE_ALIAS');

		// Automatically fix the type
		if (!in_array($this->type, ['components', 'libraries', 'modules', 'packages', 'plugins', 'files', 'templates']))
		{
			$this->type = 'components';
		}

		/**
		 * Force the client_id based on type for everything except modules and templates.
		 *
		 * This is a Joomla requirement which cannot be implemented client-side without a custom field type and
		 * JavaScript. Instead, we are simply hiding the client_id field for components, libraries, packages, plugins
		 * and files extensions, handing the forced client_id over here!
		 */
		switch ($this->type)
		{
			case 'components':
				$this->client_id = 1;
				break;

			case 'libraries':
			case 'packages':
			case 'plugins':
			case 'files':
				$this->client_id = 0;
				break;
		}

		// Require an element name
		$this->assertNotEmpty($this->element, 'COM_ARS_UPDATESTREAM_ERR_NEEDS_ELEMENT');

		if (empty($this->published) && ($this->published !== 0))
		{
			$this->published = 0;
		}
	}
}
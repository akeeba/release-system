<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\AssertionAware;
use Akeeba\Component\ARS\Administrator\Table\Mixin\CreateModifyAware;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Utilities\ArrayHelper;
use RuntimeException;

/**
 * ARS Add-on Download IDs table
 *
 * @property int    $id                Primary key
 * @property int    $user_id           FK to #__users
 * @property int    $primary           Is this the primary Download ID for the user?
 * @property string $title             Item title
 * @property string $dlid              Download ID
 * @property int    $published         Publish state
 * @property string $created           Created date and time
 * @property int    $created_by        Created by this user
 * @property string $modified          Modified date and time
 * @property int    $modified_by       Modified by this user
 * @property int    $checked_out       Checked out by this user
 * @property string $checked_out_time  Checked out date and time
 */
class DlidlabelTable extends AbstractTable
{
	use CreateModifyAware;
	use AssertionAware;

	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $_supportNullValue = false;

	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__ars_dlidlabels', ['id'], $db);

		$this->created_by = Factory::getApplication()->getIdentity()->id;
		$this->created    = Factory::getDate()->toSql();
	}

	protected function onBeforeCheck()
	{
		$app = Factory::getApplication();

		if (($app instanceof CMSApplication) && $app->isClient('site'))
		{
			// Frontend: force using the current user by setting the user_id column to null.
			$this->user_id = null;
		}

		// If no user_id is selected use the current user's ID.
		$this->user_id = $this->user_id ?: Factory::getApplication()->getIdentity()->id;

		// Make sure the user_id points to a valid user record
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->user_id);
		$this->assert($this->user_id == $user->id, '');

		// Decide if this is a primary or secondary Download ID, overriding the user's selection if necessary.
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__ars_dlidlabels'))
			->where($db->qn('user_id') . ' = ' . $db->q($this->user_id))
			->where($db->qn('primary') . ' = ' . $db->q(1));

		if ($this->id)
		{
			$query->where($db->qn('id') . ' != :id')
				->bind(':id', $this->id);
		}

		$this->primary = ($db->setQuery($query)->loadResult() ?? 0) ? 0 : 1;

		// Primary Download IDs are always published and have a title of "_MAIN_".
		if ($this->primary)
		{
			// You can never disable a primary Download ID
			$this->published = 1;
			// The primary Download ID title is fixed
			$this->title = '_MAIN_';
		}

		// Do I need to create a new download ID?
		if (empty($this->dlid))
		{
			$this->createNewDownloadID($db);
		}

		$this->title = $this->title ?: sprintf('Download ID %s' , (new Date())->format(Text::_('DATE_FORMAT_LC6') . ' T'));
	}

	/**
	 * Create a new, random, Download ID which does not collide with any other existing ones.
	 *
	 * You can trigger this code by setting the 'dlid' property to NULL and saving the record.
	 *
	 * @param   DatabaseDriver  $db
	 *
	 * @throws  Exception
	 */
	private function createNewDownloadID(DatabaseDriver $db): void
	{
		while (true)
		{
			$this->dlid = md5(random_bytes(64));

			// Do I have another primary?
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->qn('#__ars_dlidlabels'))
				->where($db->qn('dlid') . ' = ' . $db->q($this->dlid))
				->where($db->qn('primary') . ' = ' . $db->q($this->primary));

			// Non-primary Download IDs only need to be unique for the specific user
			if ($this->primary != 1)
			{
				$query
					->where($db->qn('user_id') . ' = ' . $db->q($this->user_id));
			}

			// If it's an existing record it's OK to reuse the same Download ID (probability: 1 in 2^32).
			if ($this->id)
			{
				$query->where($db->qn('id') . ' != :id')
					->bind(':id', $this->id);
			}

			// If no collision was found accept the Download ID and go away.
			if (($db->setQuery($query)->loadResult() ?? 0) == 0)
			{
				break;
			}
		}
	}

	/**
	 * Disallow unpublishing the Main Download ID of a user.
	 *
	 * @param   null  $pks
	 * @param   int   $state
	 * @param   int   $userId
	 *
	 * @throws  RuntimeException
	 */
	protected function onBeforePublish($pks = null, $state = 1, $userId = 0)
	{
		// We only need to check what happens when we try to unpublish a record
		if ($state != 0)
		{
			return;
		}

		// If there are no keys we're only checking this here record.
		if (empty($pks))
		{
			if (($this->primary != 0))
			{
				throw new RuntimeException(Text::_("COM_ARS_DLIDLABELS_ERR_CANTUNPUBLISHDEFAULT"));
			}

			return;
		}

		// We have pure integer IDs so that's what I'm gonna check
		$pks = ArrayHelper::toInteger($pks);

		foreach ($pks as $id)
		{
			$record = clone $this;
			$record->reset();

			if (!$record->load($id) || !$record->primary)
			{
				continue;
			}

			throw new RuntimeException(Text::_("COM_ARS_DLIDLABELS_ERR_CANTUNPUBLISHDEFAULT"));
		}
	}
}
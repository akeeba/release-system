<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\EnsureUcmTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableAssertionTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableColumnAliasTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableCreateModifyTrait;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\Event;

/**
 * ARS Releases table
 *
 * @property int    $id                Primary key
 * @property int    $category_id       FK to #__ars_categories
 * @property string $version           Release title a.k.a. version
 * @property string $alias             Release alias for URL generation
 * @property string $maturity          Release maturity: 'alpha','beta','rc','stable'
 * @property int    $security          Security severity: 0 none, 1 low, 2 medium, 3 high, 4 critical
 * @property string $notes             Release notes, displayed in frontend
 * @property string $hits              Hits (times displayed)
 * @property string $created           Created date and time
 * @property int    $created_by        Created by this user
 * @property string $modified          Modified date and time
 * @property int    $modified_by       Modified by this user
 * @property int    $checked_out       Checked out by this user
 * @property string $checked_out_time  Checked out date and time
 * @property int    $ordering          Front-end ordering
 * @property int    $access            Joomla view access level
 * @property int    $show_unauth_links Should I show unauthorized links?
 * @property string $redirect_unauth   Where should I redirect unauthorised access to?
 * @property int    $published         Publish state
 * @property string $language          Language code, '*' for all languages.
 */
class ReleaseTable extends AbstractTable implements TaggableTableInterface
{
	use TaggableTableTrait;
	use TableCreateModifyTrait;
	use TableAssertionTrait;
	use TableColumnAliasTrait;
	use EnsureUcmTrait;

	/**
	 * Used internally by Joomla! to manage tags.
	 *
	 * @var   null|array
	 * @since 7.4.0
	 */
	public ?array $newTags;

	/**
	 * The UCM type alias. Used for tags, content versioning etc. Leave blank to effectively disable these features.
	 *
	 * @var    string
	 * @since  7.4.0
	 */
	public $typeAlias = 'com_ars.release';

	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var    boolean
	 * @since  7.0.0
	 */
	protected $_supportNullValue = false;

	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__ars_releases', ['id'], $db);

		$this->setColumnAlias('catid', 'category_id');
		$this->setColumnAlias('title', 'version');

		$user             = Factory::getApplication()->getIdentity();
		$this->created_by = $user?->id;
		$this->created    = Factory::getDate()->toSql();
		$this->access     = 1;
	}

	/**
	 * Get the type alias for the tags mapping table
	 *
	 * The type alias generally is the internal component name with the content type. Ex.: com_content.article
	 *
	 * @return  string  The alias as described above
	 *
	 * @since   7.4.0
	 */
	public function getTypeAlias(): string
	{
		return $this->typeAlias;
	}

	/**
	 * Performs some evil voodoo to work around a baffling issue I discovered.
	 *
	 * After enabling the taggable behaviour the BleedingEdge releases stopped working.
	 *
	 * The problem comes from the Taggable behaviour handling onTableBeforeStore in
	 * \Joomla\Plugin\Behaviour\Taggable\Extension\Taggable::onTableBeforeStore. Debugging it I can see that it calls
	 * \Joomla\CMS\Helper\TagsHelper::preStoreProcess which creates a clone of this table object, then calls reset() on
	 * the clone. The original table's properties remain unchanged, EXCEPT FOR the category_id column which becomes
	 * NULL. For the life of me, I cannot understand why category_id is being reset on both the original table, and the
	 * clone. I suspect something, somehow is being passed by reference, but I don't know where.
	 *
	 * Instead of spending several hair-pulling, profanity-filled hours with a dubious result I decided to invoke the
	 * Dark Arts and work around the issue with the elegance of taking a sledgehammer to it. It's crude, but it bloody
	 * works!
	 *
	 * The idea is simple. The whole problem happens because of the taggable behaviour's
	 * \Joomla\Plugin\Behaviour\Taggable\Extension\Taggable::onTableBeforeStore. This is what calls the TagsHelper which
	 * triggers the weird problem. I do not have another event in the table object to hook into before Joomla! tries to
	 * write anything to the database. Therefore, I need to create a TEMPORARY event handler for onTableBeforeStore
	 * which will be called AFTER the Taggable one and which will restore the category ID. We do that with the
	 * combination of our trusted onBeforeStore and onAfterStore methods.
	 *
	 * onBeforeStore caches the category ID after casting it into a string and concatenating it with a marker. This
	 * ensures that if there was any by-reference passing it will break, as we force the PHP engine to create a new
	 * string out of the value. It also caches the SPL object hash of the table we are working on, just in case we fail
	 * to clean up and end up running our temporary handler outside the intended context.
	 *
	 * The handler, voodooOnBeforeStore, makes sure we are processing the onTableBeforeStore event of the very same
	 * table object it was created for by checking the SPL object hash. It will then read the cached voodoo value of the
	 * category_id, cast it back to an int or null, and assign it to the (original) table's category_id, thus bypassing
	 * the issue caused by the taggable behaviour.
	 *
	 * onAfterStore will kick in after storing the database record to remove the cached voodoo values and remove the
	 * temporary voodooOnBeforeStore event handler from the events dispatcher object, thereby cleaning up.
	 *
	 * And yet, this convoluted method was far more efficient than trying to figure out WTF is actually going on.
	 *
	 * It's not stupid if it works. Right?
	 *
	 * @param   Event  $e
	 *
	 * @return  void
	 * @see     self::onBeforeStore()
	 * @see     self::onAfterStore()
	 */
	public function voodooOnBeforeStore(Event $e)
	{
		if ($e->getName() !== 'onTableBeforeStore')
		{
			return;
		}

		[$subject, $updateNulls, $k] = array_values($e->getArguments());

		if (!$subject instanceof ReleaseTable)
		{
			return;
		}

		if (spl_object_hash($subject) !== ($this->_voodoo_hash ?? null))
		{
			return;
		}

		$voodooValue = substr($this->_voodoo_category_id, 7);
		$voodooValue = empty($voodooValue) ? null : intval($voodooValue);

		$subject->category_id = $voodooValue;
	}

	/**
	 * Runs after loading a record from the database
	 *
	 * @param   bool   $result  Did the record load?
	 * @param   mixed  $keys    The keys used to load the record.
	 * @param   bool   $reset   Was I asked to reset the object before loading the record?
	 *
	 * @return  void
	 *
	 * @since   7.4.0
	 */
	protected function onAfterLoad(bool &$result, $keys, bool $reset): void
	{
		// Make sure existing records have a UCM record
		if (!$result || !empty($this->id))
		{
			$this->ensureUcmRecord();
		}
	}

	/**
	 * Part of the evil voodoo.
	 *
	 * @return  void
	 * @see     self::voodooOnBeforeStore()
	 */
	protected function onBeforeStore()
	{
		$this->_voodoo_category_id = 'VOODOO:' . (string) ($this->category_id ?? '');
		$this->_voodoo_hash        = spl_object_hash($this);

		$this->getDispatcher()->addListener('onTableBeforeStore', [$this, 'voodooOnBeforeStore']);
	}

	/**
	 * Part of the evil voodoo.
	 *
	 * @return  void
	 * @see     self::voodooOnBeforeStore()
	 */
	protected function onAfterStore()
	{
		unset ($this->_voodoo_category_id);
		unset ($this->_voodoo_hash);

		$this->getDispatcher()->removeListener('onTableAfterStore', [$this, 'voodooOnBeforeStore']);
	}

	protected function onBeforeCheck()
	{
		$this->assertNotEmpty($this->category_id, 'COM_ARS_RELEASE_ERR_NEEDS_CATEGORY');
		$this->assertNotEmpty($this->version, 'COM_ARS_RELEASE_ERR_NEEDS_VERSION');

		// If the alias is missing, auto-create a new one
		if (!$this->alias)
		{
			$this->alias = ApplicationHelper::stringURLSafe(strtolower($this->version));
		}

		// If no alias could be auto-generated, fail
		$this->assertNotEmpty($this->alias, 'COM_ARS_CATEGORY_ERR_NEEDS_SLUG');

		// Check alias for uniqueness
		$db    = $this->getDbo();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select(
				[
					$db->quoteName('alias'),
					$db->quoteName('version'),
				]
			)
			->from($db->quoteName('#__ars_releases'))
			->where($db->quoteName('category_id') . ' = :catid')
			->bind(':catid', $this->category_id);

		if ($this->id)
		{
			$query->where($db->qn('id') . ' != :id')
				->bind(':id', $this->id);
		}

		$existingItems = $db->setQuery($query)->loadAssocList('alias', 'version');

		$this->assertNotInArray(
			$this->version, array_values($existingItems), 'COM_ARS_RELEASE_ERR_NEEDS_VERSION_UNIQUE'
		);

		$this->assertNotInArray($this->alias, array_keys($existingItems), 'COM_ARS_RELEASE_ERR_NEEDS_ALIAS_UNIQUE');

		// Automatically fix the maturity
		if (!in_array($this->maturity, ['alpha', 'beta', 'rc', 'stable']))
		{
			$this->maturity = 'beta';
		}

		// Clamp the security severity to [0, 4]
		$this->security = max(0, min((int) ($this->security ?? 0), 4));

		/**
		 * Filter the notes using a safe HTML filter.
		 *
		 * Yes, the form does filter the input BUT this table may be used outside the backend controller. This is an
		 * extra precaution to ensure we're not missing anything.
		 */
		if (!empty($this->notes))
		{
			$filter      = InputFilter::getInstance([], [], 1, 1);
			$this->notes = $filter->clean($this->notes);
		}

		// Set the default access level
		if ($this->access <= 0)
		{
			$this->access = 1;
		}

		// Clamp 'published' to [0, 1]
		$this->published = max(0, min($this->published, 1));

		// Make sure a non-empty ordering is set
		$this->ordering = $this->ordering ?? 0;
	}
}
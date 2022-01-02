<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\Mixin\ClearCacheAfterActions;
use FOF40\Container\Container;
use FOF40\Model\DataModel;
use FOF40\Model\Mixin\Assertions;
use JDatabaseQuery;
use Joomla\CMS\Crypt\Crypt;
use Joomla\CMS\Language\Text;

/**
 * Model for add-on Download IDs
 *
 * Fields:
 *
 * @property  int     $ars_dlidlabel_id
 * @property  int     $user_id
 * @property  bool    $primary
 * @property  string  $label
 * @property  string  $dlid
 *
 * Filters:
 *
 * @method  $this  ars_dlidlabel_id()  ars_dlidlabel_id(int $v)
 * @method  $this  user_id()           user_id(int $v)
 * @method  $this  primary()           primary(bool $v)
 * @method  $this  label()             label(string $v)
 * @method  $this  dlid()              dlid(string $v)
 * @method  $this  enabled()           enabled(bool $v)
 * @method  $this  created_by()        created_by(int $v)
 * @method  $this  created_on()        created_on(string $v)
 * @method  $this  modified_by()       modified_by(int $v)
 * @method  $this  modified_on()       modified_on(string $v)
 *
 */
class DownloadIDLabels extends DataModel
{
	use Assertions;
	use ClearCacheAfterActions;

	/**
	 * Public constructor. Overrides the parent constructor.
	 *
	 * @param   Container  $container  The configuration variables to this model
	 * @param   array      $config     Configuration values for this model
	 *
	 * @throws \FOF40\Model\DataModel\Exception\NoTableColumns
	 * @see DataModel::__construct()
	 *
	 */
	public function __construct(Container $container, array $config = array())
	{
		$config['tableName'] = '#__ars_dlidlabels';
		$config['idFieldName'] = 'ars_dlidlabel_id';

		parent::__construct($container, $config);

		// Behaviours
		$this->addBehaviour('Filters');
		$this->addBehaviour('Created');
		$this->addBehaviour('Modified');
	}

	/**
	 * Implements custom filtering
	 *
	 * @param JDatabaseQuery $query          The model query we're operating on
	 * @param   bool         $overrideLimits Are we told to override limits?
	 *
	 * @return  void
	 */
	protected function onBeforeBuildQuery(JDatabaseQuery &$query, bool $overrideLimits = false): void
	{
		$db = $this->getDbo();

		$fltUsername = $this->getState('username', null, 'string');

		if ($fltUsername)
		{
			$fltUsername = '%' . $fltUsername . '%';
			$q = $db->getQuery(true)
					->select(array(
						$db->qn('id')
					))->from($db->qn('#__users'))
					->where($db->qn('username') . ' LIKE ' . $db->q($fltUsername), 'OR')
					->where($db->qn('name') . ' LIKE ' . $db->q($fltUsername))
					->where($db->qn('email') . ' LIKE ' . $db->q($fltUsername));
			$db->setQuery($q);
			$ids = $db->loadColumn();

			if (!empty($ids))
			{
				$ids = array_map(array($db, 'quote'), $ids);

				$query->where($db->qn('user_id') . 'IN (' . implode(',', $ids) . ')');
			}
			else
			{
				$query->where($db->qn('user_id') . '=' . $db->q(0));
			}
		}
	}

	protected function onBeforeDelete(&$id): void
	{
		if ($this->primary)
		{
			throw new \RuntimeException(Text::_('COM_ARS_DLIDLABELS_ERR_CANTDELETEDEFAULT'));
		}
	}

	public function check(): self
	{
		if ($this->container->platform->isFrontend())
		{
			$this->user_id = $this->container->platform->getUser()->id;
		}

		$db = $this->getDbo();

		// Should this be a primary or a secondary DLID?
		if (is_null($this->primary))
		{
			// Do I have another primary?
			$query = $db->getQuery(true)
						->select('COUNT(*)')
						->from($db->qn('#__ars_dlidlabels'))
						->where($db->qn('user_id') . ' = ' . $db->q($this->user_id))
						->where($db->qn('primary') . ' = ' . $db->q(1));

			if ($this->ars_dlidlabel_id)
			{
				$query->where('NOT(' . $db->qn('ars_dlidlabel_id') . ' = ' . $db->q($this->ars_dlidlabel_id) . ')');
			}

			$hasPrimary = $db->setQuery($query)->loadResult();

			$this->primary = $hasPrimary ? 0 : 1;
		}

		if ($this->primary)
		{
			// You can never disable a primary Download ID
			$this->enabled = 1;
			// The primary Download ID title is fixed
			$this->label = '_MAIN_';
		}

		// Do I need to generate a download ID?
		if (empty($this->dlid))
		{
			while (empty($this->dlid))
			{
				$this->dlid = md5(random_bytes(64));

				// Do I have another primary?
				$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->qn('#__ars_dlidlabels'))
					->where($db->qn('dlid') . ' = ' . $db->q($this->dlid))
					->where($db->qn('user_id') . ' = ' . $db->q($this->user_id))
					->where($db->qn('primary') . ' = ' . $db->q($this->primary));

				if ($this->ars_dlidlabel_id)
				{
					$query->where('NOT(' . $db->qn('ars_dlidlabel_id') . ' = ' . $db->q($this->ars_dlidlabel_id) . ')');
				}

				$dlidColission = $db->setQuery($query)->loadResult();

				if ($dlidColission)
				{
					$this->dlid = null;
				}
			}
		}

		return parent::check();
	}

	protected function onBeforeUnpublish(): void
	{
		if ($this->primary)
		{
			throw new \RuntimeException(Text::_("COM_ARS_DLIDLABELS_ERR_CANTUNPUBLISHDEFAULT"));
		}
	}
}

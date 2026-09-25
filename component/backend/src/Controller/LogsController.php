<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Input\Input;

class LogsController extends AdminController
{
	use ControllerEvents;

	protected $text_prefix = 'COM_ARS_LOGS';

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->unregisterTask('publish');
		$this->unregisterTask('unpublish');
		$this->unregisterTask('archive');
		$this->unregisterTask('trash');
		$this->unregisterTask('report');
		$this->unregisterTask('orderup');
		$this->unregisterTask('orderdown');
		$this->unregisterTask('orderdown');
		$this->unregisterTask('reorder');
		$this->unregisterTask('saveorder');
		$this->unregisterTask('checkin');
		$this->unregisterTask('saveOrderAjax');
		$this->unregisterTask('runTransition');
	}

	public function getModel($name = 'Log', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Clear the cached, unfiltered download log row count.
	 *
	 * @return  void
	 *
	 * @throws  NotAllowed  When the user lacks the core.manage privilege on the component.
	 */
	public function clearLogs(): void
	{
		$this->checkToken();

		// Housekeeping task, restricted to users who can manage the component.
		if (!$this->app->getIdentity()->authorise('core.manage', 'com_ars'))
		{
			throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->getModel('Logs')->cleanCachedTotal();

		$this->setMessage(Text::_('COM_ARS_LOGS_CACHE_CLEARED'));
		$this->setRedirect($this->getRedirectUrlToList());
	}

	protected function postDeleteHook(BaseDatabaseModel $model, $id = null)
	{
		parent::postDeleteHook($model, $id);

		// The unfiltered list count is cached; make a deletion visible on the very next page load.
		$this->getModel('Logs')->cleanCachedTotal();
	}

}
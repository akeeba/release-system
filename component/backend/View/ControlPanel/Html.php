<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\ControlPanel;

use Akeeba\ReleaseSystem\Admin\Helper\Cache;
use Akeeba\ReleaseSystem\Admin\Model\ControlPanel;
use FOF40\Date\Date;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class Html extends \FOF40\View\DataView\Html
{
	/** @var  int  Total number of downloads this month */
	public $downloadsMonth = 0;

	/** @var  int  Total number of downloads this week */
	public $downloadsWeek = 0;

	/** @var  array  Information to plot the downloads per day for the past month */
	public $monthlyDailyReport = [];

	/** @var  int  Graphs width in Bootstrap columns (1 to 12) */
	public $graphsWidth = 0;

	/** @var  string  Currently installed version of the component */
	public $currentVersion = '0.0.0';

	/** @var  Date  When the odl PHP version will be reported */
	public $akeebaCommonDatePHP = '';

	/** @var  Date  When we are supposed to stop supporting the obsolete PHP version */
	public $akeebaCommonDateObsolescence = '';

	public $needsMenuItem;

	/**
	 * Executes before rendering the 'main' task's view template
	 *
	 * @param   string|null  $tpl  Currently unused
	 *
	 * @return  void
	 * @throws \Exception
	 */
	protected function onBeforeMain($tpl = null): void
	{
		/** @var ControlPanel $model */
		$model = $this->getModel();

		// Get the cache
		$cache = new Cache();

		// Download details (Downloads per Month, Week, All Time)
		$dldetails = $cache->getValue('dldetails', '{}');
		$dldetails = @json_decode($dldetails, true) ?? [
				'dlmonth' => 0,
				'dlweek'  => 0,
			];

		if (
			empty($dldetails) ||
			!isset($dldetails['dlmonth']) || empty($dldetails['dlmonth']) ||
			!isset($dldetails['dlweek']) || empty($dldetails['dlweek'])
		)
		{
			$dldetails = [
				'dlmonth' => $model->getNumDownloads('month'),
				'dlweek'  => $model->getNumDownloads('week'),
			];

			$cache->setValue('dldetails', json_encode($dldetails));
		}

		$this->downloadsMonth = $dldetails['dlmonth'];
		$this->downloadsWeek  = $dldetails['dlweek'];

		// -- Monthly-Daily downloads report
		$mdReport = $cache->getValue('mdreport');

		if (empty($mdReport))
		{
			$mdReport = json_encode($model->getMonthlyStats());
			$cache->setvalue('mdreport', $mdReport);
		}

		$this->monthlyDailyReport = json_decode($mdReport, true);

		// Save/update the cache
		$cache->save();

		// Get chart area width
		$params = ComponentHelper::getParams('com_ars');
		$width  = $params->get('graphswidth', 8);

		if ($width < 2)
		{
			$width = 2;
		}
		elseif ($width > 10)
		{
			$width = 10;
		}

		$this->graphsWidth = $width;

		$this->needsMenuItem = $model->needsCategoriesMenu();

		// Information for the PHP version warning
		$this->akeebaCommonDatePHP          = $this->container->platform->getDate('2015-08-14 00:00:00', 'GMT')->format(Text::_('DATE_FORMAT_LC1'));
		$this->akeebaCommonDateObsolescence = $this->container->platform->getDate('2016-05-14 00:00:00', 'GMT')->format(Text::_('DATE_FORMAT_LC1'));
	}
}

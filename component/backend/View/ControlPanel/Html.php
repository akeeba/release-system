<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\View\ControlPanel;

use Akeeba\ReleaseSystem\Admin\Helper\Cache;
use Akeeba\ReleaseSystem\Admin\Model\ControlPanel;
use JComponentHelper;
use JLoader;

defined('_JEXEC') or die;

class Html extends \FOF30\View\DataView\Html
{
	/** @var  array  GUI icon definitions */
	public $iconDefinitions = [];

	/** @var  array  Popular downloads this week */
	public $popularInWeek = [];

	/** @var  int  Total number of downloads this month */
	public $downloadsMonth = 0;

	/** @var  int  Total number of downloads this week */
	public $downloadsWeek = 0;

	/** @var  int  Total number of downloads (for all time) */
	public $downloadsEver = 0;

	/** @var  array  Information to plot the downloads per day for the past month */
	public $monthlyDailyReport = [];

	/** @var  int  Graphs width in Bootstrap columns (1 to 12) */
	public $graphsWidth = 0;

	/** @var  bool  Is the GeoIP plugin installed? */
	public $hasGeoIPPlugin = false;

	/** @var  bool  Does the GeoIP plugin needs to update its GeoIP database? */
	public $geoIPPluginNeedsUpdate = false;

	/** @var  string  Currently installed version of the component */
	public $currentVersion = '0.0.0';

	/** @var  \JDate  When the odl PHP version will be reported */
	public $akeebaCommonDatePHP = '';

	/** @var  \JDate  When we are supposed to stop supporting the obsolete PHP version */
	public $akeebaCommonDateObsolescence = '';

	public $needsMenuItem;

	/**
	 * Executes before rendering the 'main' task's view template
	 *
	 * @param   string|null  $tpl  Currently unused
	 *
	 * @return  void
	 */
	protected function onBeforeMain($tpl = null)
	{
		/** @var ControlPanel $model */
		$model = $this->getModel();

		// Icon definitions
		$this->iconDefinitions = $model->getIconDefinitions();

		// Get the cache
		$cache = new Cache();

		// Popular This Week
		$popularWeek = $cache->getValue('popularweek');

		if (empty($popularWeek))
		{
			$popularWeek = json_encode($model->getWeekPopular());
			$cache->setValue('popularweek', $popularWeek);
		}

		$this->popularInWeek = json_decode($popularWeek);

		// Download details (Downloads per Month, Week, All Time)
		$dldetails = $cache->getValue('dldetails');

		if (empty($dldetails))
		{
			$cache->setValue('dldetails', json_encode([
				'dlmonth' => $model->getNumDownloads('month'),
				'dlweek'  => $model->getNumDownloads('week'),
				'dlever'  => $model->getNumDownloads('alltime'),
			]));
		}

		$dldetails = json_decode($dldetails, true);

		$this->downloadsMonth = $dldetails['dlmonth'];
		$this->downloadsWeek  = $dldetails['dlweek'];
		$this->downloadsEver  = $dldetails['dlever'];

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
		JLoader::import('joomla.application.component.helper');
		$params = JComponentHelper::getParams('com_ars');
		$width = $params->get('graphswidth', 8);

		if ($width < 2)
		{
			$width = 2;
		}
		elseif ($width > 10)
		{
			$width = 10;
		}

		$this->graphsWidth = $width;

		$this->needsMenuItem  = $model->needsCategoriesMenu();
		$this->hasGeoIPPlugin = $model->hasGeoIPPlugin();
		$this->geoIPPluginNeedsUpdate = $model->GeoIPDBNeedsUpdate();

		// Information for the PHP version warning
		$this->akeebaCommonDatePHP = \JFactory::getDate('2015-08-14 00:00:00', 'GMT')->format(\JText::_('DATE_FORMAT_LC1'));
		$this->akeebaCommonDateObsolescence = \JFactory::getDate('2016-05-14 00:00:00', 'GMT')->format(\JText::_('DATE_FORMAT_LC1'));
	}
}
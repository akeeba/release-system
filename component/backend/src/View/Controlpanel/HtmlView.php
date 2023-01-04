<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\View\Controlpanel;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Helper\Cache;
use Akeeba\Component\ARS\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\ARS\Administrator\Model\ControlpanelModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	use ViewTaskBasedEventsTrait;

	/** @var  int  Total number of downloads this month */
	public $downloadsMonth = 0;

	/** @var  int  Total number of downloads this week */
	public $downloadsWeek = 0;

	/** @var  array  Information to plot the downloads per day for the past month */
	public $monthlyDailyReport = [];

	/** @var  int  Graphs width in Bootstrap columns (1 to 12) */
	public $graphsWidth = 0;

	/** @var bool Do I still need a menu item for the repository root? */
	public $needsMenuItem = false;

	protected function onBeforeMain(): void
	{
		ToolbarHelper::title(Text::_('COM_ARS'), 'ars');
		ToolbarHelper::preferences('com_ars');

		/** @var ControlpanelModel $model */
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

		$this->graphsWidth   = min(2, max(10, $width));
		$this->needsMenuItem = $model->needsCategoriesMenu();

		// Push chart data
		$this->document
			->addScriptOptions('akeeba.ReleaseSystem.ControlPanel.downloadsReport', array_map(function ($date, $count) {
				return [
					'date'  => $date,
					'count' => $count,
				];
			}, array_keys($this->monthlyDailyReport), $this->monthlyDailyReport));

		// Load JavaScript
		$this->document->getWebAssetManager()
			->useScript('com_ars.controlpanel');

	}

}
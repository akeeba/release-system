<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Module\Arsgraph\Administrator\Dispatcher;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Helper\Cache;
use Akeeba\Component\ARS\Administrator\Model\ControlpanelModel;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Extension\ModuleInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;

class Dispatcher extends AbstractModuleDispatcher
{
	/**
	 * The module extension. Used to fetch the module helper.
	 *
	 * @since 7.1.0
	 * @var   ModuleInterface|null
	 */
	private ?ModuleInterface $moduleExtension;

	/**
	 * The component's MVC Factory interface
	 *
	 * @since 7.1.0
	 * @var   MVCFactoryInterface|null
	 */
	private ?MVCFactoryInterface $mvcFactory;

	/** @inheritdoc */
	public function __construct(\stdClass $module, CMSApplicationInterface $app, Input $input)
	{
		parent::__construct($module, $app, $input);

		$this->moduleExtension = $this->app->bootModule('mod_arsgraph', 'administrator');

		/**
		 * DO NOT REMOVE THIS INITIALISATION!
		 *
		 * We want to always boot the com_ars component so at the very least it loads its language files. We are
		 * using them in the display.
		 */
		$hasArs           = ComponentHelper::isInstalled('com_ars') && ComponentHelper::isEnabled('com_ars');
		$this->mvcFactory = $hasArs
			? $this->getApplication()->bootComponent('com_ars')->getMVCFactory()
			: null;
	}

	protected function getLayoutData()
	{
		$hasArs = $this->mvcFactory !== null;

		if ($hasArs)
		{
			$this->app->getLanguage()->load('com_ars', JPATH_ADMINISTRATOR);

			/** @var HtmlDocument $document */
			$document        = $this->app->getDocument();
			$webAssetManager = $document->getWebAssetManager();

			// DO NOT REMOVE — This registers the component's Web Asset Manager stuff
			$webAssetManager->getRegistry()->addExtensionRegistryFile('com_ars');
			$webAssetManager
				->usePreset('com_ars.backend')
				->useScript('com_ars.controlpanel');

			// Add the graph information to the document
			$monthlyDailyReport = $this->getMonthlyDailyReport();
			$document
				->addScriptOptions(
					'akeeba.ReleaseSystem.ControlPanel.downloadsReport', array_map(function ($date, $count) {
					return [
						'date'  => $date,
						'count' => $count,
					];
				}, array_keys($monthlyDailyReport), $monthlyDailyReport)
				);
		}

		return array_merge(
			parent::getLayoutData(),
			[
				'hasArs' => $hasArs,
			]
		);
	}

	private function getMonthlyDailyReport(): array
	{
		// Get the cache
		$cache = new Cache();

		// -- Monthly-Daily downloads report
		$mdReport = $cache->getValue('mdreport');

		if (empty($mdReport))
		{
			/** @var ControlpanelModel $model */
			$model    = $this->mvcFactory
				->createModel('Controlpanel', 'Administrator', ['ignore_request' => true]);
			$mdReport = json_encode($model->getMonthlyStats());
			$cache->setvalue('mdreport', $mdReport);
		}

		$monthlyDailyReport = json_decode($mdReport, true);

		// Save/update the cache
		$cache->save();

		return $monthlyDailyReport ?: [];
	}
}
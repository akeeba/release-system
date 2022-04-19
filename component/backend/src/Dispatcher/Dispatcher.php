<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Dispatcher;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Administrator\Mixin\TriggerEvent;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Document\HtmlDocument;
use Throwable;

class Dispatcher extends ComponentDispatcher
{
	use TriggerEvent;

	protected $defaultController = 'controlpanel';

	protected $viewMap = [
		'downloadidlabel'  => 'dlidlabel',
		'downloadidlabels' => 'dlidlabels',
	];

	public function dispatch()
	{
		// Check the minimum supported PHP version
		$minPHPVersion = '7.3.0';
		$softwareName  = 'Akeeba Release System';
		$silentResults = $this->app->isClient('site');

		if (!@include_once JPATH_ADMINISTRATOR . '/components/com_ars/tmpl/common/wrongphp.php')
		{
			return;
		}

		try
		{
			$this->triggerEvent('onBeforeDispatch');

			parent::dispatch();

			// This will only execute if there is no redirection set by the Controller
			$this->triggerEvent('onAfterDispatch');
		}
		catch (Throwable $e)
		{
			$title = 'Akeeba Release System';
			$isPro = false;

			if (!(include_once JPATH_ADMINISTRATOR . '/components/com_ars/tmpl/common/errorhandler.php'))
			{
				throw $e;
			}
		}
	}

	protected function onBeforeDispatch()
	{
		$this->loadLanguage();

		$this->applyViewAndController();

		$this->loadCommonStaticMedia();
	}

	protected function loadLanguage(): void
	{
		$jLang = $this->app->getLanguage();

		$jLang->load($this->option, JPATH_ADMINISTRATOR);

		if (!$this->app->isClient('administrator'))
		{
			$jLang->load($this->option, JPATH_SITE);
		}
	}

	protected function loadCommonStaticMedia()
	{
		// Make sure we run under a CMS application
		if (!($this->app instanceof CMSApplication))
		{
			return;
		}

		// Make sure the document is HTML
		$document = $this->app->getDocument();

		if (!($document instanceof HtmlDocument))
		{
			return;
		}

		// Finally, load our 'common' preset
		$document->getWebAssetManager()
			->usePreset('com_ars.backend');

	}

	protected function applyViewAndController(): void
	{
		$controller = $this->input->getCmd('controller', null);
		$view       = $this->input->getCmd('view', null);
		$task       = $this->input->getCmd('task', 'main');

		if (strpos($task, '.') !== false)
		{
			// Explode the controller.task command.
			[$controller, $task] = explode('.', $task);
		}

		if (empty($controller) && empty($view))
		{
			$controller = $this->defaultController;
			$view       = $this->defaultController;
		}
		elseif (empty($controller) && !empty($view))
		{
			$view = $this->mapView($view);
			$controller = $view;
		}
		elseif (!empty($controller) && empty($view))
		{
			$view = $controller;
		}
		else
		{
			$view = $controller;
		}

		$controller = strtolower($controller);
		$view       = strtolower($view);

		$this->input->set('view', $view);
		$this->input->set('controller', $controller);
		$this->input->set('task', $task);
	}

	protected function mapView(string $view)
	{
		$view = strtolower($view);

		return $this->viewMap[$view] ?? $view;
	}
}
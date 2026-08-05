<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\ARS\Site\Mixin\ControllerDisplayTrait;
use Akeeba\Component\ARS\Site\Model\UpdateModel;
use Akeeba\Component\ARS\Site\View\Update\IniView;
use Akeeba\Component\ARS\Site\View\Update\XmlView;
use Joomla\CMS\Document\FactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use RuntimeException;

class UpdateController extends BaseController
{
	use ControllerEvents;
	use ControllerRegisterTasksTrait;
	use ControllerDisplayTrait;

	/**
	 * Show all updates
	 */
	public function all(): void
	{
		$registeredURLParams = [
			'option' => 'CMD',
			'view'   => 'CMD',
			'task'   => 'CMD',
			'format' => 'CMD',
			'layout' => 'CMD',
			'id'     => 'CMD',
			'dlid'   => 'STRING',
		];

		$this->display(true, $registeredURLParams);
	}

	/**
	 * Show updates for a category
	 *
	 * @throws RuntimeException
	 * @throws \Exception
	 */
	public function category(): void
	{
		$cat = $this->input->getCmd('id', '');

		if (empty($cat))
		{
			// Do we have a menu item parameter?
			$cat = $this->app->getParams('com_ars')->get('category', 'components');
		}

		if (empty($cat))
		{
			throw new RuntimeException(Text::_('COM_ARS_COMMON_ERR_NOUPDATESOURCE'), 500);
		}

		// Required for caching
		$this->input->set('id', $cat);

		/** @var UpdateModel $model */
		$model    = $this->getModel('update');
		$envModel = $this->getModel('environments', 'site', ['ignore_request' => true]);
		/** @var XmlView $view */
		$view           = $this->getView('update', 'xml');
		$view->items    = $model->getCategoryItems($cat);
		$view->category = $cat;
		$view->setModel($envModel, false);

		$registeredURLParams = [
			'option' => 'CMD',
			'view'   => 'CMD',
			'task'   => 'CMD',
			'format' => 'CMD',
			'layout' => 'CMD',
			'id'     => 'INT',
			'dlid'   => 'STRING',
		];

		$this->display(true, $registeredURLParams);
	}

	/**
	 * Show an update stream
	 *
	 * @throws RuntimeException
	 * @throws \Exception
	 */
	public function stream(): void
	{
		$id = $this->input->getInt('id', 0);

		if ($id == 0)
		{
			// Since 7.3.3 we use a fake URL parameter `stream_id`
			$id = $this->input->getInt('stream_id', $id);

			// Legacy menu items may have a menu item parameter.
			$params = $this->app->getParams('com_ars');
			$id     = $id ?: $params->get('streamid', $params->get('stream_id', $id));
		}

		// Define the Id for caching as if it were received as a safeuri param
		$this->app->getInput()->set('id', $id);

		/** @var UpdateModel $model */
		$model           = $this->getModel();
		$envModel        = $this->getModel('environments', 'site', ['ignore_request' => true]);
		$view            = $this->getView();
		$view->items     = $model->getItems($id);
		$view->published = $model->getPublished($id);
		$view->category  = $id;
		$view->setModel($envModel, false);


		$registeredURLParams = [
			'option' => 'CMD',
			'view'   => 'CMD',
			'task'   => 'CMD',
			'format' => 'CMD',
			'layout' => 'CMD',
			'id'     => 'INT',
			'dlid'   => 'STRING',
		];

		$this->display(true, $registeredURLParams);
	}

	public function json(): void
	{
		$this->stream();
	}

	/**
	 * Show an INI formatted update stream
	 *
	 * @throws RuntimeException
	 * @throws \Exception
	 */
	public function ini(): void
	{
		$id = $this->input->getInt('id', 0);

		if ($id == 0)
		{
			// Do we have a menu item parameter?
			$params = $this->app->getParams('com_ars');
			$id     = $params->get('streamid', 0);
			$id     = $params->get('stream_id', $id);
		}

		// Define the Id for caching as if it were received as a safeuri param
		$this->app->getInput()->set('id', $id);

		/** @var UpdateModel $model */
		$model = $this->getModel();
		/** @var IniView $view */
		$view            = $this->getView('update', 'ini');
		$view->items     = $model->getItems($id);
		$view->published = $model->getPublished($id);

		$envModel = $this->getModel('environments', 'site', ['ignore_request' => true]);
		$view->setModel($envModel, false);

		$this->app->getDocument()->setType('ini');

		$registeredURLParams = [
			'option' => 'CMD',
			'view'   => 'CMD',
			'task'   => 'CMD',
			'format' => 'CMD',
			'layout' => 'CMD',
			'id'     => 'INT',
			'dlid'   => 'STRING',
		];

		$this->display(true, $registeredURLParams);
	}

	/**
	 * Downloads the latest version of a software given its update stream ID
	 *
	 * @throws RuntimeException
	 * @throws \Exception
	 */
	public function download(): void
	{
		$id = $this->input->getInt('id', 0);

		if ($id == 0)
		{
			// Do we have a menu item parameter?
			$params = $this->app->getParams('com_ars');
			$id     = $params->get('streamid', 0);
			$id     = $params->get('stream_id', $id);
		}

		// Define the Id for caching as if it were received as a safeuri param
		$this->app->getInput()->set('id', $id);

		/** @var UpdateModel $model */
		$model     = $this->getModel();
		$items     = $model->getItems($id);
		$published = $model->getPublished($id);

		if (!$published)
		{
			// This stream isn't published.
			throw new RuntimeException('Not Found', 404);
		}

		if (empty($items))
		{
			// No items to display. What are you doing here? Are you lost?
			throw new RuntimeException('Not Found', 404);
		}

		// Get the download item
		$downloadItem = array_shift($items);

		$this->input->set('item_id', $downloadItem->item_id ?? 0);
		/** @var ItemController $downloadController */
		$downloadController = $this->factory->createController('item', 'site', [], $this->app, $this->input);

		// Is this a JED Install From Web request?
		$installAt  = $this->input->getBase64('installat');
		$installApp = $this->input->getBase64('installapp');

		$downloadController->execute('download');
		$downloadController->redirect();
	}

	protected function onBeforeExecute(&$task)
	{
		$task   = $this->input->getCmd('task', '');
		$layout = $this->input->getCmd('layout', '');
		$id     = $this->input->getInt('id');
		$format = $this->input->getCmd('format', 'html');

		// If we're told to render this view as HTML it's a routing error, so let's fall back to an XML update stream
		if (!in_array($format, ['xml', 'ini', 'raw', 'json']))
		{
			$this->input->set('format', 'xml');
			$format = 'xml';
		}

		// Very old menu items used layout instead of task. Let's cater for them.
		$task = (empty($task) || ($task == 'main') && !empty($layout)) ? $layout : $task;

		// If there is no task or layout we will use the default task depending on the view type
		if (empty($task) || ($task == 'main'))
		{
			switch ($format)
			{
				default:
				case 'xml':
					$task = 'all';
					break;

				case 'ini':
					$task = 'ini';
					break;

				case 'json':
					$task = 'json';
					break;

				case 'raw':
					$task = 'download';
					break;
			}
		}

		// INI, JSON, and RAW views require an `id` query parameter, otherwise they are invalid and I can fail the early.
		if (($format != 'xml') && empty($id))
		{
			throw new RuntimeException(Text::_('COM_ARS_COMMON_ERR_NOUPDATESOURCE'), 500);
		}

		// Ensure the task and view type combination makes sense
		switch ($task)
		{
			case 'ini':
				$format = 'ini';
				break;

			case 'json':
				$format = 'json';
				break;

			case 'download':
				$format = 'raw';
				break;

			default:
				if (!in_array($task, ['all', 'category', 'stream', 'jed']))
				{
					$task = 'all';
				}

				/**
				 * The `json` task is nothing but the `stream` task rendered as JSON — `json()` literally calls
				 * `stream()`. Therefore `task=stream&format=json` means the JSON update stream, and asking for it
				 * must not silently hand back XML. Every other task is XML-only.
				 */
				if (($task === 'stream') && ($format === 'json'))
				{
					$task = 'json';
				}
				else
				{
					$format = 'xml';
				}
				break;
		}

		// Set our modified variables back into the request...
		$this->input->set('task', $task);
		$this->input->set('format', $format);
		$this->input->set('layout', null);

		// ...and to the Controller itself
		$this->task = $task;

		// Make sure the CMS application has a document object of the correct type
		$actualViewType = ($format === 'ini') ? 'raw' : $format;

		if (strtolower($this->app->getDocument()->getType()) != $actualViewType)
		{
			$newDocument = Factory::getContainer()->get(FactoryInterface::class)->createDocument($actualViewType, []);

			$this->app->loadDocument($newDocument);

			$this->repairStaleDocumentBuffer();
		}
	}

	protected function onAfterExecute($task)
	{
		$format = $this->input->getCmd('format', 'html');

		if (!in_array($format, ['xml', 'ini', 'json']))
		{
			$this->app->close();
		}
	}

	/**
	 * Undo the damage caused by swapping the application's document object mid-request.
	 *
	 * `SiteApplication::dispatch()` takes a reference to the document BEFORE it renders the component, then stashes
	 * the component's output in it with `$document->setBuffer($contents, ['type' => 'component'])`. We have just
	 * replaced the application's document with one of a different type, so that call goes to the OLD object — an
	 * HtmlDocument whenever the request carried no `format` in the URL, which is exactly the case this method exists
	 * for.
	 *
	 * `Document::$_buffer` is static, i.e. every Document object in the request shares it, but the two document
	 * flavours disagree about its shape: HtmlDocument nests it as `$_buffer[$type][$name][$title]`, while Xml, Json
	 * and Raw documents keep the body in it verbatim and hand it straight back from `render()`. The result was a
	 * response body of the literal string "Array", preceded by an "Array to string conversion" warning, tacked onto
	 * the end of the update stream.
	 *
	 * Flatten the buffer back to a plain string once the CMS is done writing to it and before it is rendered.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	private function repairStaleDocumentBuffer(): void
	{
		$this->app->getDispatcher()->addListener(
			'onAfterDispatch',
			function () {
				$document = $this->app->getDocument();
				$buffer   = $document->getBuffer();

				if (!is_array($buffer))
				{
					return;
				}

				$document->setBuffer($buffer['component'][''][''] ?? '');
			}
		);
	}
}
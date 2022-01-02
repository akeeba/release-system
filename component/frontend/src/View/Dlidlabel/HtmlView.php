<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\View\Dlidlabel;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Model\CategoryModel;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;

class HtmlView extends BaseHtmlView
{
	/**
	 * The Form object
	 *
	 * @var    Form
	 * @since  1.5
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var    object
	 * @since  1.5
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var    object
	 * @since  1.5
	 */
	protected $state;

	/**
	 * Base64-encoded return URL for the list form and all action URLs
	 *
	 * @var   string
	 * @since 7.0.5
	 */
	public $returnURL;

	/** @inheritdoc */
	public function display($tpl = null): void
	{
		/** @var CategoryModel $model */
		$model       = $this->getModel();
		$this->form  = $model->getForm();
		$this->item  = $model->getItem();
		$this->state = $model->getState();

		// We always need to force the layout in the frontend.
		$this->setLayout('edit');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		$this->returnURL = $this->returnURL ?: Route::_('index.php?option=com_ars&view=dlidlabels');

		parent::display($tpl);
	}

	/**
	 * Set up the toolbar
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   7.0.0
	 */
	protected function addToolbar(): void
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$isNew = empty($this->item->id);

		$bar = Toolbar::getInstance('toolbar');
		$bar->save('dlidlabel.save', 'JSAVE')
			->buttonClass('btn btn-success me-2');

		$bar->appendButton('Standard', 'cancel', 'JCANCEL', 'dlidlabel.cancel', false);
	}
}
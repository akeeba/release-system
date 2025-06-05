<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\EditorsExtended\ARSLink\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

class ARSLink extends CMSPlugin implements SubscriberInterface
{
	/** @var CMSApplication */
	protected $app;

	protected $autoloadLanguage = true;

	public static function getSubscribedEvents(): array
	{
		return ['onEditorButtonsSetup' => 'onEditorButtonsSetup'];
	}

	/**
	 * @param  EditorButtonsSetupEvent $event
	 * @return void
	 *
	 * @since   5.0.0
	 */
	public function onEditorButtonsSetup(EditorButtonsSetupEvent $event): void
	{
		$subject  = $event->getButtonsRegistry();
		$disabled = $event->getDisabledButtons();

		if (\in_array($this->_name, $disabled)) {
			return;
		}

		$this->loadLanguage();

		$button = $this->onDisplay($event->getEditorId());

		if ($button) {
			$subject->add($button);
		}
	}

	/**
	 * @param   string  $name
	 *
	 * @return  CMSObject|Button|null
	 */
	public function onDisplay(string $name)
	{
		static $hasSetJS = false;

		if (!ComponentHelper::isEnabled('com_ars'))
		{
			return null;
		}

		$doc = $this->app->getDocument();

		if (!$hasSetJS)
		{
			$hasSetJS = true;

			/**
			 * Javascript to insert the link.
			 *
			 * View element calls arsSelectItem when an item is clicked.
			 * arsSelectItem creates the link tag, sends it to the editor, and closes the select frame.
			 */
			$js = <<<JS


;// This comment is intentionally put here to prevent badly written plugins from causing a Javascript error
// due to missing trailing semicolon and/or newline in their code.
function arsSelectItem(id, title)
{
	var editor = Joomla.getOptions('xtd-arslink').editor;

	if (Joomla.editors.instances[editor].getSelection()) {
      Joomla.editors.instances[editor].replaceSelection(
          '<a href=\"index.php?option=com_ars&amp;view=item&amp;task=download&amp;format=raw&amp;item_id='+id+'\">'+
          Joomla.editors.instances[editor].getSelection()
          +'</a>'
      );
    } else {
      Joomla.editors.instances[editor].replaceSelection(
          '<a href=\"index.php?option=com_ars&amp;view=item&amp;task=download&amp;format=raw&amp;item_id='+id+'\">'+title+'</a>'
      );
    }

	if (Joomla.Modal) {
      Joomla.Modal.getCurrent().close();
    }
}

JS;

			$doc->getWebAssetManager()
				->addInlineScript($js);
		}

		$doc->addScriptOptions(
			'xtd-arslink', [
				'editor' => $name,
			]
		);

		$props = [
			'modal'   => true,
			'link'    => sprintf(
				'index.php?option=com_ars&view=items&layout=modal&tmpl=component&%s=1',
				$this->app->getFormToken()
			),
			'text'    => Text::_('PLG_ARSITEM_BUTTON_ITEM'),
			'name'    => $this->_type . '_' . $this->_name,
			'icon'    => 'download fa fa-file-download',
			'iconSVG' => file_get_contents(JPATH_ROOT . '/media/com_ars/icons/logo_color.svg'),
		];

		$options = [
			'height'     => '400px',
			'width'      => '800px',
			'bodyHeight' => '70',
			'modalWidth' => '80',
		];

		if (version_compare(JVERSION, '5.0.0', 'lt'))
		{
			return new CMSObject(
				array_merge(
					$props,
					[
						'options' => $options,
					],
				)
			);
		}

		return new Button($this->_name, $props, $options);
	}
}

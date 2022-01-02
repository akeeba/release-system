<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// no direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/**
 * Editor ARS link buton
 */
class plgButtonArslink extends CMSPlugin
{
	/**
	 * Constructor
	 *
	 * @access      protected
	 *
	 * @param       object $subject The object to observe
	 * @param       array  $config  An array that holds the plugin configuration
	 *
	 * @since       1.5
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	/**
	 * Display the button
	 *
	 * Yes, I know JObject is deprecated. However, button elements are SUPPOSED to return a JObject because Joomla tries
	 * to run get() against the returned object when rendering the buttons. Groan.
	 *
	 * @param string $name
	 *
	 * @return JObject
	 * @throws Exception
	 */
	function onDisplay(string $name): JObject
	{
		/*
		 * Javascript to insert the link
		 * View element calls arsSelectItem when an item is clicked
		 * arsSelectItem creates the link tag, sends it to the editor,
		 * and closes the select frame.
		 */
		$js = <<<JS


;// This comment is intentionally put here to prevent badly written plugins from causing a Javascript error
// due to missing trailing semicolon and/or newline in their code.
function arsSelectItem(id, title)
{
	var editor = '$name';
	var tag = '<a href='+'\"index.php?option=com_ars&amp;view=Item&amp;task=download&amp;format=raw&amp;id='+id+'\">'+title+'</a>';
	
	/** Use the API, if editor supports it **/
	if (Joomla && Joomla.editors && Joomla.editors.instances && Joomla.editors.instances.hasOwnProperty(editor))
	{
		Joomla.editors.instances[editor].replaceSelection(tag)
	}
	else
    {
		jInsertEditorText(tag, editor);
	}

	jModalClose();
}

JS;

		$doc = Factory::getDocument();
		$doc->addScriptDeclaration($js);

		$app     = Factory::getApplication();
		$rootURI = Uri::base();

		if ($app->isClient('administrator'))
		{
			$rootURI .= '../';
		}

		$css = <<<CSS
.button2-left .arsitem {
	background: url($rootURI/media/com_ars/icons/ars_logo_16_bw.png) 100% 0 no-repeat;
}
#editor-xtd-buttons span.icon-arsitem,
i.icon-arsitem {
	display: block;
	float: left;
	width: 16px;
	height: 16px;
	background: url($rootURI/media/com_ars/icons/ars_logo_16_bw.png) 0 0 no-repeat;
}
CSS;

		$doc->addStyleDeclaration($css);

		/*
		 * Use the built-in element view to select the ARS item.
		 * Currently uses blank class.
		 */
		$link =
			'index.php?option=com_ars&amp;view=Items&amp;layout=modal&amp;tmpl=component&amp;' . Session::getFormToken() . '=1';

		$props = [
			'modal'   => true,
			'class'   => 'btn',
			'link'    => $link,
			'text'    => Text::_('PLG_ARSITEM_BUTTON_ITEM'),
			'name'    => 'arsitem',
			'options' => "{handler: 'iframe', size: {x: 800, y: 400}}",
		];

		return new JObject($props);
	}
}

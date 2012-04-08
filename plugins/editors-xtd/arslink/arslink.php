<?php
/**
 * @package AkeebaReleaseSystem
 * @subpackage plugins.arslink
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * Editor ARS link buton
 */
class plgButtonArslink extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @access      protected
	 * @param       object  $subject The object to observe
	 * @param       array   $config  An array that holds the plugin configuration
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
	 * @return array A four element array of (article_id, article_title, category_id, object)
	 */
	function onDisplay($name)
	{
		/*
		 * Javascript to insert the link
		 * View element calls arsSelectItem when an item is clicked
		 * arsSelectItem creates the link tag, sends it to the editor,
		 * and closes the select frame.
		 */
		$js = "
		function arsSelectItem(id, title) {
			var tag = '<a href='+'\"index.php?option=com_ars&amp;view=download&amp;id='+id+'\">'+title+'</a>';
			jInsertEditorText(tag, '".$name."');
			SqueezeBox.close();
		}";

		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration($js);
		
		$app = JFactory::getApplication();
		$tmpl = $app->getTemplate();
		$doc->addStyleDeclaration(".button2-left .arsitem {background: url(templates/$tmpl/images/j_button2_readmore.png) 100% 0 no-repeat;}");

		JHtml::_('behavior.modal');

		/*
		 * Use the built-in element view to select the article.
		 * Currently uses blank class.
		 */
		$link = 'index.php?option=com_ars&amp;view=items&amp;layout=modal&amp;tmpl=component';

		$button = new JObject();
		$button->set('modal', true);
		$button->set('link', $link);
		$button->set('text', JText::_('PLG_ARSITEM_BUTTON_ITEM'));
		$button->set('name', 'arsitem');
		$button->set('options', "{handler: 'iframe', size: {x: 770, y: 400}}");

		return $button;
	}
}

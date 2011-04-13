<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted access');

/**
 * A centralized place to include ARS's CSS and JS files to the rendered page, as well as
 * GUI-related helper functions
 * @author Nicholas
 */
class ArsHelperIncludes
{
	/** @var array The URLs of external scripts I've got to load*/
	public static $scriptURLs = array();

	/** @var array script definitions I want to inject right after the external scripts */
	public static $scriptDefs = array();

	static function getScriptDefs()
	{
		$media_folder = JURI::base().'../media/com_ars/';
		$scriptDefs = array(
			$media_folder.'js/gui-helpers.js'
		);
		return $scriptDefs;
	}

	/**
	 * Includes ARS's Javascript files
	 */
	static function includeJS()
	{
		// Load jQuery
		self::jQueryLoad();
		self::jQueryUILoad();

		$document =& JFactory::getDocument();

		// In Joomla! 1.6 we have to load jQuery and jQuery UI without the hackish onAfterRender method :(
		global $mainframe;
		if(!is_object($mainframe))
		{
			foreach(self::$scriptURLs as $url)
			{
				$document->addScript($url);
			}
			foreach(self::$scriptDefs as $script)
			{
				$document->addScriptDeclaration($script);
			}
		}

		// Joomla! 1.5 method
		$scriptDefs = self::getScriptDefs();
		if(!empty($scriptDefs)) foreach($scriptDefs as $scriptURI)
		{
			$document->addScript($scriptURI);
		}
	}

	/**
	 * Includes ARS's CSS files
	 */
	static function includeCSS()
	{
		$media_folder = JURI::base().'../media/com_ars/';
		$document =& JFactory::getDocument();
		$document->addStyleSheet($media_folder.'theme/jquery-ui.css');
		$document->addStyleSheet($media_folder.'css/backend.css');
	}

	/**
	 * Includes ARS's media (CSS & JS) files. It's a shorthand to the other two functions.
	 */
	static function includeMedia()
	{
		self::includeJS();
		self::includeCSS();
	}

	/**
	 * Loads jQuery from its respective source
	 */
	static function jQueryLoad()
	{
		$js = JURI::base().'../media/com_ars/js/jquery.js';
		self::$scriptURLs[] = $js;
	}

	/**
	 * Loads jQuery UI from its respective source
	 */
	static function jQueryUILoad()
	{
		$js = JURI::base().'../media/com_ars/js/jquery-ui.js';
		self::$scriptURLs[] = $js;
	}
}

/**
 * This is an ARS hack to make sure that its own JS is going to be loaded before the one loaded by any
 * funky system plug-in. For example, many stupid plugins default to loading jQuery 1.2.6 in the backend.
 * WTF?! This is an ancient version! And why the hell load it in the backend anyway?! So, instead of having
 * to educate webmasters that the plugins work in a stupid way and plugin authors how not to write stupid
 * scripts (can't really blame newbies for being ignorant), I work around this issue by writing my hidden
 * system plug-in. Yeap! This is actually a system plugin :p It will grab the HTML and drop its own JS in
 * the head of the script, before anything else has the chance to run.
 *
 * Peace.
 */
function ARSScriptHook()
{
	global $mainframe;

	// If there are no script defs, just go to sleep
	if(empty(ARSHelperIncludes::$scriptURLs) && empty(ARSHelperIncludes::$scriptDefs) ) return;

	$myscripts = '';
	if(!empty(ARSHelperIncludes::$scriptURLs)) foreach(ARSHelperIncludes::$scriptURLs as $url)
	{
		$myscripts .= '<script type="text/javascript" src="'.$url.'"></script>'."\n";
	}
	if(!empty(ARSHelperIncludes::$scriptDefs))
	{
		$myscripts .= '<script type="text/javascript">'."\n";
		foreach(ARSHelperIncludes::$scriptDefs as $def)
		{
			$myscripts .= $def."\n";
		}
		$myscripts .= '</script>'."\n";
	}

	$buffer = JResponse::getBody();
	$pos = strpos($buffer, "<head>");
	if($pos > 0)
	{
		$buffer = substr($buffer, 0, $pos + 6).$myscripts.substr($buffer, $pos + 6);
		JResponse::setBody($buffer);
	}
}

$app = &JFactory::getApplication();
$app->registerEvent('onAfterRender', 'ARSScriptHook');
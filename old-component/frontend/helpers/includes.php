<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
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
		$media_folder = JURI::base().'/media/com_ars/';
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

		$document = JFactory::getDocument();

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
		$media_folder = rtrim(JURI::base(),'/').'/media/com_ars/';
		$document = JFactory::getDocument();
		$document->addStyleSheet($media_folder.'theme/jquery-ui.css');
		$document->addStyleSheet($media_folder.'css/frontend.css');
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
		$document = JFactory::getDocument();
		$document->addScript(JURI::base().'media/com_ars/js/akeebajq.js');
	}

	/**
	 * Loads jQuery UI from its respective source
	 */
	static function jQueryUILoad()
	{
		$document = JFactory::getDocument();
		$document->addScript(JURI::base().'media/com_ars/js/akeebajqui.js');
	}
}
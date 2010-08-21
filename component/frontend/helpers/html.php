<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class ArsHelperHtml
{
	/**
	 * Processes the message, replacing placeholders with their values and running any
	 * plug-ins
	 * @param string $message The message to process
	 * @return string The processed message
	 */
	static public function preProcessMessage($message)
	{
		// Parse [SITE]
		$site_url = JURI::base();
		$message = str_replace('[SITE]', $site_url, $message);

		// Run content plug-ins
		$message = JHTML::_('content.prepare', $message);

		// Return the value
		return $message;
	}

	static public function sizeFormat($filesize)
	{
		if($filesize > 1073741824) {
			return number_format($filesize / 1073741824, 2)." Gb";
		} elseif($filesize >= 1048576) {
			return number_format($filesize / 1048576, 2)." Mb";
		} elseif($filesize >= 1024) {
			return number_format($filesize / 1024, 2)." Kb";
		} else {
			return $filesize." bytes";
		}
	}
}
<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * A sample plugin to demonstrate how you can modify Bleeding Edge releases on the
 * fly. Feel free to create your own plugins to customise Bleeding Edge's behaviour.
 */
class plgArsBleedingedgematurity extends JPlugin
{
	/**
	 * This is the even which is being triggered by ARS every time it creates a
	 * new Bleeding Edge release.
	 *
	 * The $info array contains the following keys:
	 * - folder            string    The subfolder of the new release
	 * - category_id    int        The numeric category ID the release belongs to
	 * - category        object    The category row object
	 * - has_changelog    bool    True if a CHANGELOG file is present
	 * - changelog_file    string    The name of the changelog file (always CHANGELOG for now)
	 * - changelog        string    Raw contents of the CHANGELOG file
	 * - first_changelog string    Raw contents of the CHANGELOG file of the earliest published release in the category
	 *
	 * The $data array contains all the information for creating the new category:
	 * - id                int        Leave it 0 or you will suffer eternal torment!
	 * - category_id    int        Category ID; please don't touch
	 * - version        string    The version number string.
	 * - alias            string    The alias. Since it MUST match the folder name DO NOT CHANGE.
	 * - maturity        string    alpha, beta, rc or stable. Defaults to alpha.
	 * - description    string    If you want, set a long description (HTML); default empty
	 * - notes            string    The contents of the release ntoes (HTML); default is determined by CHANGELOG contents
	 * - groups            string    The AMBRA.subs/Akeeba Subs groups which can access this release
	 * - access            int        Access level / View level
	 * - published        int        Obviously, you need it to be 1
	 *
	 * Return the modified $data array, or false if you have made no modifications
	 *
	 * @param array $info
	 * @param array $data
	 *
	 * @return array|bool
	 */
	public function onNewARSBleedingEdgeRelease($info, $data)
	{
		$folderName = strtoupper($info['folder']);
		$parts = explode('_', $folderName);

		if (count($parts) < 2)
		{
			return false;
		}
		$stability = array_pop($parts);

		switch ($stability)
		{
			case 'ALPHA':
				$data['maturity'] = 'alpha';
				break;

			case 'BETA':
				$data['maturity'] = 'beta';
				break;

			case 'RC':
				$data['maturity'] = 'rc';
				break;

			case 'STABLE':
				$data['maturity'] = 'stable';
				break;

			default:
				return false;
		}

		// If this line breaks something on yoru site:
		$version = strtolower(implode('_', $parts));
		// comment it and uncomment the following line:
		// $version = strtolower(implode('_', $parts));
		// and don't ask me to fix anything. I've wasted too much time on this already.

		$data['version'] = $version;

		return $data;
	}
}

<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use FOF40\Update\Update;

class Updates extends Update
{
	/**
	 * Public constructor. Initialises the protected members as well.
	 *
	 * @param   array  $config
	 */
	public function __construct($config = [])
	{
		$config['update_component'] = 'pkg_ars';
		$config['update_sitename']  = 'Akeeba Release System';
		$config['update_site']      = 'https://cdn.akeeba.com/updates/ars.xml';

		parent::__construct($config);
	}
}
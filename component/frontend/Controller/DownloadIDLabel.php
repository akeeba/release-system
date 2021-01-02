<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Controller\DownloadIDLabels;
use FOF30\Container\Container;
use Joomla\CMS\Router\Route;

class DownloadIDLabel extends DownloadIDLabels
{
	public function __construct(Container $container, array $config = array())
	{
		$config['taskPrivileges'] = [
			'publish'   => 'true',
			'unpublish' => 'true',
			'save'      => 'true',
			'savenew'   => 'true',
			'remove'    => 'true',
		];

		parent::__construct($container, $config);

		$this->input->set('user_id', $this->container->platform->getUser()->id);
	}

	/**
	 * Overrides the default display method to add caching support
	 *
	 * @param   bool         $cachable   Is this a cacheable view?
	 * @param   bool|array   $urlparams  Registered URL parameters
	 * @param   null|string  $tpl        Sub-template (not really used...)
	 */
	public function display($cachable = false, $urlparams = false, $tpl = null): void
	{
		$cachable = false;

		parent::display($cachable, $urlparams, $tpl);
	}

	public function setRedirect($url, $msg = null, $type = null)
	{
		if (substr($url, 0, 10) == 'index.php?')
		{
			$url = Route::_($url);
		}

		return parent::setRedirect($url, $msg, $type);
	}
}

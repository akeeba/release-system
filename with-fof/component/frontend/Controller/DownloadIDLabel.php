<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Controller\DownloadIDLabels;
use FOF40\Container\Container;
use FOF40\Controller\Controller;
use Joomla\CMS\Router\Route;

class DownloadIDLabel extends DownloadIDLabels
{
	public function __construct(Container $container, array $config = [])
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
	public function display(bool $cachable = false, ?array $urlparams = null, ?string $tpl = null): void
	{
		$cachable = false;

		parent::display($cachable, $urlparams, $tpl);
	}

	public function setRedirect(string $url, ?string $msg = null, ?string $type = null): Controller
	{
		if (substr($url, 0, 10) == 'index.php?')
		{
			$url = Route::_($url);
		}

		return parent::setRedirect($url, $msg, $type);
	}
}

<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Controller\DownloadIDLabels;
use FOF30\Container\Container;
use FOF30\Controller\DataController;

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
}
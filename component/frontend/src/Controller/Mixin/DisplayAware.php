<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller\Mixin;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ReusableModels;
use Akeeba\Component\ARS\Administrator\Mixin\TriggerEvent;
use Exception;
use Joomla\CMS\MVC\Controller\BaseController;

trait DisplayAware
{
	use TriggerEvent;
	use ReusableModels;

	/**
	 * Default page caching parameters.
	 *
	 * @var string[]
	 */
	protected static $defaultUrlParams = [
		'limit'            => 'UINT',
		'limitstart'       => 'UINT',
		'filter_order'     => 'CMD',
		'filter_order_Dir' => 'CMD',
		'lang'             => 'CMD',
		'Itemid'           => 'INT',
	];

	/**
	 * Default display method.
	 *
	 * Use onBeforeDisplay and onAfterDisplay to customise this.
	 *
	 * @param   bool   $cachable
	 * @param   array  $urlparams
	 *
	 * @return  BaseController
	 * @throws  Exception
	 */
	public function display($cachable = true, $urlparams = [])
	{
		$this->triggerEvent('onBeforeDisplay', [&$cachable, &$urlparams]);

		$ret = parent::display($cachable, $urlparams);

		$this->triggerEvent('onAfterDisplay', [&$ret]);

		return $ret;
	}

}
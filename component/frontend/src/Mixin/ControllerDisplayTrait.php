<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Mixin;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TriggerEventTrait;
use Exception;
use Joomla\CMS\MVC\Controller\BaseController;

trait ControllerDisplayTrait
{
	use TriggerEventTrait;
	use ControllerReusableModelsTrait;

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
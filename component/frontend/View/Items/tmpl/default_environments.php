<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\Model\Items  $model */

if (!empty($fieldValue))
{
	foreach ($fieldValue as $environment)
	{
		echo \Akeeba\ReleaseSystem\Admin\Helper\Select::environmentIcon($environment);
	}
}
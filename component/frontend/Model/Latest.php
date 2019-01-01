<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Model;

defined('_JEXEC') or die();

use Akeeba\ReleaseSystem\Admin\Model\Releases as AdminReleases;

/**
 * This model extends from the admin Releases model ON PURPOSE. DO NOT modify the Controller to use the front- or
 * backend Releases model. See https://github.com/akeeba/release-system/issues/121
 *
 * In short, if you use the same model with the same name in two different views the model state will bleed over from
 * one view to the other. The reason is that the state is saved by the Model, using a hash which includes the component
 * and model name. In a future version of FOF 3 we will allow changing the hash to be used by the each Model instance
 * returned by the Controller to avoid this issue. In the meantime we can simply create a new Model that extends the
 * existing model but with a different name, therefore a different hash.
 *
 * @package     Akeeba\ReleaseSystem\Site\Model
 *
 * @since       3.2.5
 */
class Latest extends AdminReleases
{
}

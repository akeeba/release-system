<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') || die;

use Joomla\CMS\Toolbar\Toolbar;

/**
 * Trait for retrieving the view's toolbar.
 */
trait ViewToolbarTrait
{
    /**
     * Get the toolbar attached to this view's document.
     *
     * @return  Toolbar
     */
    protected function getToolbarCompat(): Toolbar
    {
        return $this->getDocument()->getToolbar();
    }
}

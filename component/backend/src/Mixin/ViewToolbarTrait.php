<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

use Joomla\CMS\Toolbar\Toolbar;

/**
 * Trait for handling toolbar compatibility between Joomla 4.x and 5.x
 */
trait ViewToolbarTrait
{
    /**
     * Get the toolbar in a way that's compatible with both Joomla 4.x and 5.x
     *
     * @return  Toolbar
     */
    protected function getToolbarCompat(): Toolbar
    {
        $document = $this->getDocument();

        // Joomla 5 and later
        if (method_exists($document, 'getToolbar'))
        {
            return $document->getToolbar();
        }

        // Joomla 4.x
        /** @noinspection PhpDeprecationInspection */
        return Toolbar::getInstance('toolbar');
    }
}

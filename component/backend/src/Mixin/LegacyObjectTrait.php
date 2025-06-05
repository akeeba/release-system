<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') || die;

use Joomla\CMS\Object\CMSObject;

/**
 * Trait for normalizing CMS objects
 */
trait LegacyObjectTrait
{
    /**
     * Normalizes a possible CMS object to a standard object
     *
     * @param   mixed  $item  The item to normalize
     *
     * @return  mixed  The normalized item
     */
    private function normalizePossibleCMSObject($item)
    {
        if (!is_object($item))
        {
            return $item;
        }

        /** @noinspection PhpDeprecationInspection */
        if (class_exists(CMSObject::class) && !$item instanceof CMSObject)
        {
            return $item;
        }

        /** @noinspection PhpDeprecationInspection */
        return (object) $item->getProperties();
    }
}
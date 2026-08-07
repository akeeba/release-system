<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') || die;

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

        // Joomla hands us objects from $model->getItem(). On current Joomla releases those are
        // Table instances, which inherit from CMSObject and therefore expose getProperties(). The
        // original implementation gated this branch on class_exists(CMSObject::class), but the
        // class is being phased out and will not exist on upcoming Joomla releases. We instead
        // check whether the object itself implements getProperties() — Joomla documents that as
        // the contract for objects whose data lives in public properties.
        //
        // If getProperties() exists we delegate to it; otherwise we mirror Joomla's
        // LegacyPropertyManagementTrait::getProperties(true) ourselves, extracting the public
        // properties (excluding underscore-prefixed and non-public ones).
        if (method_exists($item, 'getProperties'))
        {
            /** @noinspection PhpDeprecationInspection */
            return (object) $item->getProperties();
        }

        return (object) self::extractPublicProperties($item);
    }

    /**
     * Mirrors Joomla\CMS\Object\LegacyPropertyManagementTrait::getProperties(true) so that objects
     * without a getProperties() method still get a sensible public-properties extraction. Underscore-
     * prefixed property names and any private/protected property are dropped, matching Joomla's own
     * semantics so that callers see the same shape whether or not the object still inherits from
     * CMSObject.
     *
     * @param   object  $item  The object to inspect.
     *
     * @return  array  The public, non-underscore-prefixed property map.
     */
    private static function extractPublicProperties(object $item): array
    {
        $vars = get_object_vars($item);

        foreach ($vars as $key => $value)
        {
            if (str_starts_with((string) $key, '_'))
            {
                unset($vars[$key]);
            }
        }

        $nonePublicProperties = [];
        $reflection           = new \ReflectionObject($item);

        do
        {
            $nonePublicProperties = array_merge(
                $reflection->getProperties(\ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED),
                $nonePublicProperties
            );
        }
        while ($reflection = $reflection->getParentClass());

        foreach ($nonePublicProperties as $prop)
        {
            if (\array_key_exists($prop->getName(), $vars))
            {
                unset($vars[$prop->getName()]);
            }
        }

        return $vars;
    }
}
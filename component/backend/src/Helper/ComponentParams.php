<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Helper;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Helper\DbQuery;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

class ComponentParams
{
	/**
	 * Actually Save the params into the db
	 *
	 * @param   Registry  $params
	 *
	 * @since   9.0.0
	 */
	public static function save(Registry $params, string $option = 'com_ars'): void
	{
		/** @var DatabaseDriver $db */
		$db   = JoomlaFactory::getContainer()->get(DatabaseInterface::class);
		$data = $params->toString('JSON');

		$sql = DbQuery::create($db)
			->update($db->qn('#__extensions'))
			->set($db->qn('params') . ' = ' . $db->q($data))
			->where($db->qn('element') . ' = :option')
			->where($db->qn('type') . ' = ' . $db->q('component'))
			->bind(':option', $option);

		$db->setQuery($sql);

		try
		{
			$db->execute();

			// The component parameters are cached. We just changed them. Therefore we MUST reset the system cache which holds them.
			CacheCleaner::clearCacheGroups(['_system'], [0, 1]);
		}
		catch (\Exception $e)
		{
			// Don't sweat if it fails
		}

		// Reset ComponentHelper's cache
		$refClass = new \ReflectionClass(ComponentHelper::class);
		$refProp  = $refClass->getProperty('components');

		if (version_compare(PHP_VERSION, '8.3.0', 'ge'))
		{
			$components = $refClass->getStaticPropertyValue('components');
		}
		else
		{
			$components = $refProp->getValue();
		}

		// The cache is keyed by the option we were actually asked to save. It used to say
		// 'com_akeebabackup' here — a copy-paste from that product — which on any site without Akeeba
		// Backup installed made this an assignment to null, i.e. a fatal error. It only bit on the
		// first request that changed a parameter, because the database write above has already
		// happened by then and a reload finds nothing left to save.
		if (isset($components[$option]) && is_object($components[$option]))
		{
			$components[$option]->params = $params;

			if (version_compare(PHP_VERSION, '8.3.0', 'ge'))
			{
				$refClass->setStaticPropertyValue('components', $components);
			}
			else
			{
				$refProp->setValue($components);
			}
		}

	}

}
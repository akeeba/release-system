<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\Engine\Configuration;
use RuntimeException;

/**
 * Deploys and locates the identity probe.
 *
 * @see assets/e2e-probe.php for what the probe does and why it exists.
 *
 * @since 7.5.0
 */
abstract class SiteProbe
{
	/**
	 * Where the probe lives, relative to the site root.
	 *
	 * @since 7.5.0
	 */
	public const ENDPOINT = 'e2e-probe.php';

	/**
	 * Write the probe into the provisioned site's document root.
	 *
	 * @param   Configuration  $config  The suite configuration.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public static function deploy(Configuration $config): void
	{
		$source = \dirname(__DIR__) . '/assets/e2e-probe.php';
		$target = rtrim($config->getSiteRoot(), '/') . '/' . self::ENDPOINT;

		if (!is_file($source))
		{
			throw new RuntimeException(sprintf('The probe source %s is missing.', $source));
		}

		$code = file_get_contents($source);

		if ($code === false)
		{
			throw new RuntimeException(sprintf('Could not read the probe source %s.', $source));
		}

		$code = str_replace('##SECRET##', $config->getProbeSecret(), $code);

		if (file_put_contents($target, $code) === false)
		{
			throw new RuntimeException(sprintf('Could not write the probe to %s.', $target));
		}
	}
}

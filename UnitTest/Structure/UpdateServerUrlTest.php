<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Structure;

defined('_JEXEC') or die;

use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for the M7 fix: the package manifest's `<updateservers>` entry pointed at
 * `http://cdn.akeeba.com/updates/ars.xml`. Joomla's update system fetches that URL unauthenticated, with
 * no package-signature verification, so HTTPS transport is the only protection against a
 * network-position attacker serving a forged update feed that points the installer at an
 * attacker-controlled package. See `security.md`.
 *
 * This is a plain build-time artifact (an XML manifest template, not executable code), so there is no
 * runtime seam to exercise — the only meaningful regression test is asserting the committed source of
 * truth, `build/templates/pkg_ars.xml`, never regresses to a plaintext `http://` update server URL. The
 * generated `pkg_ars.xml` copy at the repository root is gitignored/build-produced and deliberately not
 * checked here, since it may be stale or absent between builds.
 */
class UpdateServerUrlTest extends TestCase
{
	private function manifestPath(): string
	{
		return \dirname(__DIR__, 2) . '/build/templates/pkg_ars.xml';
	}

	public function testTheManifestTemplateStillExists(): void
	{
		self::assertFileExists($this->manifestPath());
	}

	public function testTheUpdateServerUrlUsesHttpsNotHttp(): void
	{
		$xml = simplexml_load_file($this->manifestPath());

		self::assertNotFalse($xml, 'build/templates/pkg_ars.xml is not well-formed XML.');

		$servers = $xml->xpath('//updateservers/server');

		self::assertNotEmpty($servers, 'No <server> entry found under <updateservers> in the manifest template.');

		foreach ($servers as $server)
		{
			$url = trim((string) $server);

			self::assertStringStartsNotWith(
				'http://',
				$url,
				"Update server URL '{$url}' uses plaintext HTTP; it must be https:// (see security.md, M7)."
			);

			self::assertStringStartsWith(
				'https://',
				$url,
				"Update server URL '{$url}' does not use https:// at all."
			);
		}
	}
}

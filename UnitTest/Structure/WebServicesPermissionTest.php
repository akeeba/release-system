<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Structure;

defined('_JEXEC') or die;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ARS must declare the "Access Web Services" (core.api) permission (gh-256).
 *
 * Joomla 5.4.11+ / 6.2.1+ refuse every non-public API route to a user who lacks core.api on the routed component.
 * Joomla's authorise() reads stored rules whether or not access.xml declares the action, so the end-to-end suite,
 * which writes its rules straight into #__assets, passes either way. What the declaration does is let an
 * administrator grant the permission in the component's Options, Permissions tab. Without it only Super Users can
 * use the ARS API on a fresh install. This is the test which notices it going missing.
 *
 * ARS supports Joomla versions older than 5.4.11, where Joomla's own JACTION_API language string does not exist, so
 * the action carries ARS's own label and description, which must exist in every shipped language.
 *
 * @since 7.5.3
 */
class WebServicesPermissionTest extends TestCase
{
	private static function root(): string
	{
		return \dirname(__DIR__, 2);
	}

	/**
	 * The core.api action element in the component section of access.xml, or NULL when there is none.
	 */
	private static function apiAction(): ?\SimpleXMLElement
	{
		$xml = simplexml_load_file(self::root() . '/component/backend/access.xml');

		$found = $xml->xpath('/access/section[@name="component"]/action[@name="core.api"]');

		return $found[0] ?? null;
	}

	public function testAccessXmlDeclaresCoreApi(): void
	{
		$this->assertNotNull(
			self::apiAction(),
			'component/backend/access.xml does not declare core.api, so "Access Web Services" cannot be granted on ARS.'
		);
	}

	public function testCoreApiIsDeclaredRightAfterCoreManage(): void
	{
		$xml   = simplexml_load_file(self::root() . '/component/backend/access.xml');
		$names = array_map('strval', $xml->xpath('/access/section[@name="component"]/action/@name'));
		$pos   = array_search('core.manage', $names, true);

		$this->assertNotFalse($pos, 'access.xml does not declare core.manage.');
		$this->assertSame('core.api', $names[$pos + 1] ?? null, 'core.api must come right after core.manage, as in core.');
	}

	public function testCoreApiDoesNotUseJoomlasOwnLanguageString(): void
	{
		$action = self::apiAction();

		$this->assertNotNull($action);
		$this->assertStringStartsWith(
			'COM_ARS_',
			(string) $action['title'],
			'JACTION_API only exists from Joomla 5.4.11 / 6.2.1; older versions ARS supports would show the raw key.'
		);
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function provideLanguages(): array
	{
		$languages = [];

		foreach (glob(self::root() . '/component/backend/language/*/com_ars.sys.ini') as $file)
		{
			$tag             = basename(\dirname($file));
			$languages[$tag] = [$tag];
		}

		return $languages;
	}

	#[DataProvider('provideLanguages')]
	public function testCoreApiLabelsAreTranslated(string $tag): void
	{
		$action = self::apiAction();

		$this->assertNotNull($action);

		// Parsed the way Joomla's LanguageHelper::parseIniFile() does.
		$strings = parse_ini_file(
			self::root() . '/component/backend/language/' . $tag . '/com_ars.sys.ini', false, INI_SCANNER_RAW
		);

		$this->assertIsArray($strings, sprintf('%s/com_ars.sys.ini does not parse.', $tag));

		foreach (['title', 'description'] as $attribute)
		{
			$key = (string) $action[$attribute];

			$this->assertNotSame('', $key, sprintf('The core.api action has no %s.', $attribute));
			$this->assertArrayHasKey($key, $strings, sprintf('%s/com_ars.sys.ini lacks %s.', $tag, $key));
			$this->assertNotSame('', trim($strings[$key]), sprintf('%s is empty in %s.', $key, $tag));
		}
	}
}

<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Plugin\Content\ARSLatest;

defined('_JEXEC') or die;

use Akeeba\Plugin\Content\ARSLatest\Extension\Arslatest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests {@see Arslatest::analyzeString()}, the parser for the `{arslatest ...}` content-plugin tag
 * language. It is a pure string-in, array-out method with no dependency on the rest of the plugin
 * (database, application, MVC factory), so the plugin is built with
 * `ReflectionClass::newInstanceWithoutConstructor()` purely to reach it.
 */
#[CoversClass(Arslatest::class)]
class AnalyzeStringTest extends TestCase
{
	private Arslatest $plugin;

	protected function setUp(): void
	{
		$this->plugin = (new ReflectionClass(Arslatest::class))->newInstanceWithoutConstructor();
	}

	private function analyze(string $string): array
	{
		$method = new ReflectionMethod(Arslatest::class, 'analyzeString');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$method->setAccessible(true);
		}

		return $method->invoke($this->plugin, $string);
	}

	/**
	 * @return array<string,array{0:string,1:array{0:string,1:string,2:string}}>
	 */
	public static function provideTags(): array
	{
		return [
			'RELEASE'                                    => ['release main', ['RELEASE', 'MAIN', '']],
			'RELEASE_LINK'                                => ['release_link main', ['RELEASE_LINK', 'MAIN', '']],
			"ITEM_LINK 'pattern' cat"                     => [
				"item_link 'foo*.zip' main",
				['ITEM_LINK', 'MAIN', 'FOO*.ZIP'],
			],
			'STREAM_RELEASE with an explicit pattern'     => [
				'stream_release 5 j4',
				['STREAM_RELEASE', '5', 'J4'],
			],
			'STREAM_RELEASE without a pattern defaults to ALL' => [
				'stream_release 5',
				['STREAM_RELEASE', '5', 'ALL'],
			],
			'STREAM_RELEASE_LINK'                         => [
				'stream_release_link 5 j4',
				['STREAM_RELEASE_LINK', '5', 'J4'],
			],
			'STREAM_ITEM_LINK'                             => [
				'stream_item_link 5 j4',
				['STREAM_ITEM_LINK', '5', 'J4'],
			],
			'STREAM_LINK'                                  => ['stream_link 5', ['STREAM_LINK', '5', '']],
			'INSTALLFROMWEB'                                => [
				'installfromweb main',
				['INSTALLFROMWEB', 'MAIN', ''],
			],
			'operations are case-insensitive'               => [
				'ReLeAsE main',
				['RELEASE', 'MAIN', ''],
			],
			'a quoted argument containing spaces is kept whole' => [
				"item_link 'foo bar*.zip' main",
				['ITEM_LINK', 'MAIN', 'FOO BAR*.ZIP'],
			],
			'extra whitespace around the operation and content is trimmed' => [
				'  release   main  ',
				['RELEASE', 'MAIN', ''],
			],
			'a completely unrecognised operation yields all-empty results' => [
				'bogus_op main',
				['', '', ''],
			],
			'the empty string yields all-empty results'     => ['', ['', '', '']],
			'an operation with no content argument at all yields all-empty results' => [
				'release',
				['', '', ''],
			],
			'ITEM_LINK with an unterminated quote has no content, so it yields all-empty results' => [
				"item_link 'foo*.zip'",
				['', '', ''],
			],
			'ITEM_LINK with no quoted pattern at all folds everything into content' => [
				'item_link foo*.zip main',
				['ITEM_LINK', 'FOO*.ZIP MAIN', ''],
			],
		];
	}

	#[DataProvider('provideTags')]
	public function testAnalyzeString(string $tagContent, array $expected): void
	{
		self::assertSame($expected, $this->analyze($tagContent));
	}
}

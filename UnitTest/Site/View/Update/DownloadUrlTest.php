<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Site\View\Update;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Site\View\Update\Common;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A test host for the {@see Common} trait, matching the ones used by
 * `PlatformVersionCompactorTest` / `ParsedPlatformsTest`.
 */
class DownloadUrlHost extends HtmlView
{
	use Common;

	public array $envs = [];

	public $category;
}

/**
 * Tests {@see Common::getDownloadUrl()}.
 *
 * This method always calls `Route::_()` with `Route::TLS_IGNORE` before it looks at anything else on
 * `$item`; the stub now declares that constant (and `TLS_FORCE`/`TLS_DISABLE`), which is what unblocked
 * this class — see git history for `UnitTest/Stubs/joomla-stubs.php` if that stops being true again.
 *
 * The `Route` and `Uri` stubs return their input unchanged (`Route::_()` hands back the URL verbatim;
 * `Uri::toString()` likewise), so the query string ARS built is directly observable in the return value
 * — no need to reach into a recorder. `htmlentities()` is applied to the final URL by
 * `getDownloadUrl()` itself, so `&` shows up as `&amp;` in every assertion below; that is expected, not
 * an artefact of the test.
 *
 * `Factory::getApplication()` is read for `$app->get('sef_suffix', 0)`. The stub `AbstractApplication`
 * always returns the given default regardless of key, so every test here runs under the `sef_suffix ==
 * 0` branch (the common case, and the one that leaves the URL untouched); the `sef_suffix == 1` branch
 * additionally calls `Uri::getPath()`, which the stub does not declare, so it is left uncovered here
 * rather than reaching for a workaround in a directory this agent does not own.
 */
#[CoversClass(Common::class)]
class DownloadUrlTest extends TestCase
{
	private DownloadUrlHost $host;

	protected function setUp(): void
	{
		$this->host                 = new DownloadUrlHost();
		Factory::$application       = new SiteApplication();
	}

	protected function tearDown(): void
	{
		Factory::reset();
	}

	private function item(array $overrides = []): object
	{
		return (object) array_merge(
			[
				'itemtype'   => 'file',
				'filename'   => 'myfile.zip',
				'url'        => '',
				'category'   => 5,
				'release_id' => 10,
				'item_id'    => 42,
			],
			$overrides
		);
	}

	public function testANullItemReturnsAnEmptyUrlAndFormat(): void
	{
		self::assertSame(['', ''], $this->host->getDownloadUrl(null));
	}

	/**
	 * @return array<string,array{0:string,1:string}>
	 */
	public static function provideExtensionToFormatMappings(): array
	{
		return [
			'.tar.gz maps to tgz'                     => ['myfile.tar.gz', 'tgz'],
			'.tar.bz2 maps to tbz2'                    => ['myfile.tar.bz2', 'tbz2'],
			'.tbz maps to tbz2'                        => ['myfile.tbz', 'tbz2'],
			'.tbz2 maps to tbz2'                       => ['myfile.tbz2', 'tbz2'],
			'an ordinary extension is used as-is'      => ['myfile.zip', 'zip'],
			'an extension-less filename defaults to raw' => ['myfile', 'raw'],
			'extension matching is case-insensitive'   => ['myfile.TAR.GZ', 'tgz'],
			'a mixed-case simple extension is lowercased implicitly by the general branch, but the ' .
			'stored extension itself is kept as-is' => ['myfile.ZIP', 'ZIP'],
		];
	}

	#[DataProvider('provideExtensionToFormatMappings')]
	public function testTheExtensionToFormatMapping(string $filename, string $expectedFormat): void
	{
		$item = $this->item(['filename' => $filename]);

		[$url, $format] = $this->host->getDownloadUrl($item);

		self::assertSame($expectedFormat, $format);
		self::assertSame(
			'index.php?option=com_ars&amp;view=item&amp;format=' . $expectedFormat
			. '&amp;category_id=5&amp;release_id=10&amp;item_id=42',
			$url
		);
	}

	public function testTheQueryCarriesTheItemsCategoryReleaseAndItemIds(): void
	{
		$item = $this->item([
			'filename'   => 'package.zip',
			'category'   => 7,
			'release_id' => 99,
			'item_id'    => 123,
		]);

		[$url] = $this->host->getDownloadUrl($item);

		self::assertSame(
			'index.php?option=com_ars&amp;view=item&amp;format=zip'
			. '&amp;category_id=7&amp;release_id=99&amp;item_id=123',
			$url
		);
	}

	public function testTheDlidRequestSuffixIsAppendedToTheQueryWhenSet(): void
	{
		$this->host->dlidRequest = '&dlid=' . str_repeat('a', 32);

		[$url] = $this->host->getDownloadUrl($this->item(['filename' => 'package.zip']));

		self::assertSame(
			'index.php?option=com_ars&amp;view=item&amp;format=zip'
			. '&amp;category_id=5&amp;release_id=10&amp;item_id=42&amp;dlid=' . str_repeat('a', 32),
			$url
		);
	}

	/**
	 * For a `link` item the format is still derived from the extension of `$item->url` (not
	 * `$item->filename`, which link items do not meaningfully populate), but the returned URL is the
	 * raw `$item->url` verbatim -- ARS never rewrites it through `index.php?option=com_ars...`.
	 */
	public function testALinkItemReturnsItsUrlVerbatimButStillDerivesTheFormatFromIt(): void
	{
		$item = $this->item([
			'itemtype' => 'link',
			'url'      => 'https://example.com/download/thing.tar.gz',
		]);

		self::assertSame(
			['https://example.com/download/thing.tar.gz', 'tgz'],
			$this->host->getDownloadUrl($item)
		);
	}

	public function testALinkItemWithAnOrdinaryExtensionUsesItAsTheFormat(): void
	{
		$item = $this->item([
			'itemtype' => 'link',
			'url'      => 'https://example.com/download/thing.exe',
		]);

		self::assertSame(
			['https://example.com/download/thing.exe', 'exe'],
			$this->host->getDownloadUrl($item)
		);
	}
}

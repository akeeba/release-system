<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Table;

defined('_JEXEC') or die;

use Akeeba\ARS\UnitTest\Stubs\RecordingDatabase;
use Akeeba\ARS\UnitTest\Stubs\ScriptedRecordingDatabase;
use Akeeba\Component\ARS\Administrator\Table\ItemTable;
use Joomla\CMS\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

#[CoversClass(ItemTable::class)]
#[Group('Table')]
class ItemTableTest extends TestCase
{
	protected function tearDown(): void
	{
		Factory::reset();

		parent::tearDown();
	}

	private function newItem(RecordingDatabase $db): ItemTable
	{
		$item = (new ReflectionClass(ItemTable::class))->newInstanceWithoutConstructor();
		$item->setDatabase($db);

		return $item;
	}

	private function invokeProtected(object $object, string $method, array $args = [])
	{
		$ref = new ReflectionMethod($object, $method);

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		return $ref->invokeArgs($object, $args);
	}

	// -----------------------------------------------------------------------------------------------------------
	// getUpdateStream(): fnmatch($packname ?: $element . '*', basename($filename|$url))
	// -----------------------------------------------------------------------------------------------------------

	public function testGetUpdateStreamReturnsIdOfMatchingPacknamePattern(): void
	{
		$db         = new RecordingDatabase();
		$db->result = [
			(object) ['id' => 10, 'packname' => 'awesome-*.zip', 'element' => ''],
			(object) ['id' => 11, 'packname' => 'other-*.zip', 'element' => ''],
		];

		$item             = $this->newItem($db);
		$item->release_id = 1;
		$item->type       = 'file';
		$item->filename   = 'awesome-package-1.2.3.zip';

		$this->assertSame(10, $this->invokeProtected($item, 'getUpdateStream'));
	}

	public function testGetUpdateStreamReturnsNullWhenNoPatternMatches(): void
	{
		$db         = new RecordingDatabase();
		$db->result = [(object) ['id' => 20, 'packname' => 'nomatch-*.zip', 'element' => '']];

		$item             = $this->newItem($db);
		$item->release_id = 1;
		$item->type       = 'file';
		$item->filename   = 'awesome-package-1.2.3.zip';

		$this->assertNull($this->invokeProtected($item, 'getUpdateStream'));
	}

	public function testGetUpdateStreamFallsBackToElementGlobWhenPacknameIsEmpty(): void
	{
		$db         = new RecordingDatabase();
		$db->result = [(object) ['id' => 30, 'packname' => '', 'element' => 'com_awesome']];

		$item             = $this->newItem($db);
		$item->release_id = 1;
		$item->type       = 'file';
		$item->filename   = 'com_awesome-1.2.3-stable-full_package.zip';

		$this->assertSame(30, $this->invokeProtected($item, 'getUpdateStream'));
	}

	public function testGetUpdateStreamMatchesAgainstBasenameOfAPath(): void
	{
		$db         = new RecordingDatabase();
		$db->result = [(object) ['id' => 40, 'packname' => 'thing-*.zip', 'element' => '']];

		$item             = $this->newItem($db);
		$item->release_id = 1;
		$item->type       = 'file';
		$item->filename   = '/some/deep/path/thing-1.0.zip';

		$this->assertSame(40, $this->invokeProtected($item, 'getUpdateStream'));
	}

	public function testGetUpdateStreamSkipsStreamWithBothPacknameAndElementEmpty(): void
	{
		$db         = new RecordingDatabase();
		$db->result = [(object) ['id' => 50, 'packname' => '', 'element' => '']];

		$item             = $this->newItem($db);
		$item->release_id = 1;
		$item->type       = 'file';
		$item->filename   = 'thing-1.0.zip';

		$this->assertNull($this->invokeProtected($item, 'getUpdateStream'));
	}

	public function testGetUpdateStreamUsesUrlBasenameForLinkType(): void
	{
		$db         = new RecordingDatabase();
		$db->result = [(object) ['id' => 60, 'packname' => 'linky-*', 'element' => '']];

		$item             = $this->newItem($db);
		$item->release_id = 1;
		$item->type       = 'link';
		$item->url        = 'https://example.com/dl/linky-thing.zip';

		$this->assertSame(60, $this->invokeProtected($item, 'getUpdateStream'));
	}

	// -----------------------------------------------------------------------------------------------------------
	// applyAutoDescriptions(): packname glob match against basename, applied fields
	// -----------------------------------------------------------------------------------------------------------

	private function fakeApplicationWithNoIdentity(): void
	{
		Factory::$application = new class {
			public function getIdentity()
			{
				return null;
			}
		};
	}

	public function testApplyAutoDescriptionsAppliesFieldsFromMatchingRecord(): void
	{
		$this->fakeApplicationWithNoIdentity();

		$db         = new RecordingDatabase();
		$db->result = [
			(object) [
				'id'                => 1,
				'packname'          => 'awesome-*.zip',
				'title'             => 'Auto Title',
				'description'       => 'Auto Description',
				'environments'      => '1,2',
				'access'            => 0,
				'show_unauth_links' => 0,
				'redirect_unauth'   => '',
			],
		];

		$item                     = $this->newItem($db);
		$item->release_id         = 1;
		$item->type               = 'file';
		$item->filename           = 'awesome-package-1.2.3.zip';
		$item->title              = '';
		$item->description        = '';
		$item->environments       = null;
		$item->access             = 1;
		$item->show_unauth_links  = 0;
		$item->redirect_unauth    = '';

		$this->invokeProtected($item, 'applyAutoDescriptions');

		$this->assertSame('Auto Title', $item->title);
		$this->assertSame('Auto Description', $item->description);
		$this->assertSame([1, 2], array_map('intval', $item->environments));
	}

	public function testApplyAutoDescriptionsLeavesItemUnchangedWhenNoPacknameMatches(): void
	{
		$db         = new RecordingDatabase();
		$db->result = [
			(object) [
				'id'                => 1,
				'packname'          => 'nomatch-*.zip',
				'title'             => 'Auto Title',
				'description'       => 'Auto Description',
				'environments'      => '1,2',
				'access'            => 0,
				'show_unauth_links' => 0,
				'redirect_unauth'   => '',
			],
		];

		$item             = $this->newItem($db);
		$item->release_id = 1;
		$item->type       = 'file';
		$item->filename   = 'awesome-package-1.2.3.zip';
		$item->title      = 'Original Title';

		$this->invokeProtected($item, 'applyAutoDescriptions');

		$this->assertSame('Original Title', $item->title);
	}

	public function testApplyAutoDescriptionsSkipsRecordsWithoutAPackname(): void
	{
		$db         = new RecordingDatabase();
		$db->result = [
			(object) [
				'id'                => 1,
				'packname'          => '', // Must be ignored, not treated as a wildcard match-everything.
				'title'             => 'Auto Title',
				'description'       => '',
				'environments'      => '',
				'access'            => 0,
				'show_unauth_links' => 0,
				'redirect_unauth'   => '',
			],
		];

		$item             = $this->newItem($db);
		$item->release_id = 1;
		$item->type       = 'file';
		$item->filename   = 'awesome-package-1.2.3.zip';
		$item->title      = 'Original Title';

		$this->invokeProtected($item, 'applyAutoDescriptions');

		$this->assertSame('Original Title', $item->title);
	}

	// -----------------------------------------------------------------------------------------------------------
	// onBeforeCheck(): access clamp, title/alias derivation, and the alias-uniqueness bug
	// -----------------------------------------------------------------------------------------------------------

	/**
	 * A fully-populated ItemTable, with every property onBeforeCheck() reads already set to an inert value, so a
	 * test can override just the one or two properties it cares about without tripping "Undefined property"
	 * warnings (which would fail the suite under beStrictAboutOutputDuringTests).
	 *
	 * All five hash fields plus filesize are pre-populated so the file-hashing block (which touches the filesystem
	 * and constructs ReleaseTable/CategoryTable) is skipped entirely — onBeforeCheck() only recomputes hashes when
	 * at least one of them is empty.
	 */
	private function baselineItem(RecordingDatabase $db): ItemTable
	{
		$item                    = $this->newItem($db);
		$item->id                = 0;
		$item->release_id        = 1;
		$item->type              = 'file';
		$item->filename          = 'package.zip';
		$item->url               = '';
		$item->title             = '';
		$item->alias             = '';
		$item->description       = '';
		$item->access            = 1;
		$item->published         = null;
		$item->updatestream      = null;
		$item->ordering          = null;
		$item->environments      = null;
		$item->show_unauth_links = 0;
		$item->redirect_unauth   = '';
		$item->md5               = 'x';
		$item->sha1              = 'x';
		$item->sha256            = 'x';
		$item->sha384            = 'x';
		$item->sha512            = 'x';
		$item->filesize          = 1;

		return $item;
	}

	/** A ScriptedRecordingDatabase with the title/alias, autodescription and update-stream queries all empty. */
	private function emptyScriptedDb(): ScriptedRecordingDatabase
	{
		$db          = new ScriptedRecordingDatabase();
		$db->byTable = [
			'ars_items'         => [],
			'ars_autoitemdesc'  => [],
			'ars_updatestreams' => [],
		];

		return $db;
	}

	public static function accessClampProvider(): array
	{
		return [
			'negative becomes 1' => [-5, 1],
			'zero becomes 1'     => [0, 1],
			'one stays 1'        => [1, 1],
			'five stays 5'       => [5, 5],
		];
	}

	#[DataProvider('accessClampProvider')]
	public function testAccessLessOrEqualZeroBecomesOne(int $input, int $expected): void
	{
		$item         = $this->baselineItem($this->emptyScriptedDb());
		$item->access = $input;

		$this->invokeProtected($item, 'onBeforeCheck');

		$this->assertSame($expected, $item->access);
	}

	public function testTitleAndAliasAreDerivedFromFileBasenameWhenBothAreEmpty(): void
	{
		$item           = $this->baselineItem($this->emptyScriptedDb());
		$item->filename = 'sub/dir/Awesome-Package-1.2.3.zip';
		$item->title    = '';
		$item->alias    = '';

		$this->invokeProtected($item, 'onBeforeCheck');

		$this->assertSame('Awesome-Package-1.2.3.zip', $item->title, 'Title keeps the original case and the path is stripped to the basename.');
		$this->assertSame('awesome-package-1-2-3-zip', $item->alias, 'Alias is lower-cased and non-alphanumerics collapse to dashes.');
	}

	public function testTitleAndAliasAreDerivedFromUrlBasenameForLinkType(): void
	{
		$item           = $this->baselineItem($this->emptyScriptedDb());
		$item->type     = 'link';
		$item->filename = '';
		$item->url      = 'https://example.com/dl/My-Link-File.exe';
		$item->title    = '';
		$item->alias    = '';

		$this->invokeProtected($item, 'onBeforeCheck');

		$this->assertSame('My-Link-File.exe', $item->title);
		$this->assertSame('my-link-file-exe', $item->alias);
	}

	public function testExplicitTitleAndAliasAreNotOverwritten(): void
	{
		$item        = $this->baselineItem($this->emptyScriptedDb());
		$item->title = 'Custom Title';
		$item->alias = 'custom-alias';

		$this->invokeProtected($item, 'onBeforeCheck');

		$this->assertSame('Custom Title', $item->title);
		$this->assertSame('custom-alias', $item->alias);
	}

	public function testDuplicateTitleWithinTheSameReleaseIsRejected(): void
	{
		$db          = new ScriptedRecordingDatabase();
		$db->byTable = [
			'ars_items'         => [['title' => 'Duplicate Title', 'alias' => 'other-alias']],
			'ars_autoitemdesc'  => [],
			'ars_updatestreams' => [],
		];

		$item        = $this->baselineItem($db);
		$item->title = 'Duplicate Title';
		$item->alias = 'unique-alias';

		$this->expectException(RuntimeException::class);

		$this->invokeProtected($item, 'onBeforeCheck');
	}

	/**
	 * Regression test for the array_keys()/array_values() mix-up in ItemTable::onBeforeCheck().
	 *
	 * `$info` returned by `loadAssocList('title', 'alias')` is keyed by TITLE and valued by ALIAS. The alias
	 * uniqueness check must compare the new item's alias against the OTHER items' aliases (`array_values($info)`),
	 * not their titles (`array_keys($info)`). This test ensures that an item whose alias collides with another
	 * item's alias within the same release is rejected.
	 */
	public function testDuplicateAliasWithinTheSameReleaseIsRejected(): void
	{
		$db          = new ScriptedRecordingDatabase();
		$db->byTable = [
			'ars_items'         => [['title' => 'Some Other Title', 'alias' => 'my-new-alias']],
			'ars_autoitemdesc'  => [],
			'ars_updatestreams' => [],
		];

		$item        = $this->baselineItem($db);
		$item->title = 'A Brand New Title';
		$item->alias = 'my-new-alias'; // Deliberately collides with the OTHER item's ALIAS, not its title.

		$this->expectException(RuntimeException::class);

		$this->invokeProtected($item, 'onBeforeCheck');
	}
}

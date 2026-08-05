<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Table;

defined('_JEXEC') or die;

use Akeeba\ARS\UnitTest\Stubs\ScriptedRecordingDatabase;
use Akeeba\Component\ARS\Administrator\Table\DlidlabelTable;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * Covers DlidlabelTable::onBeforeCheck(), including half of the hardening added in commit 4a9ddff0 ("Hardening:
 * authorise the frontend Download ID edit view"): `assertNotEmpty($this->user_id, 'JERROR_ALERTNOAUTHOR')` refuses
 * to store a Download ID with no owner, as defence in depth alongside the controller-level guest/ownership checks
 * added in the same commit (those live in the frontend DlidlabelController and are outside this table class).
 */
#[CoversClass(DlidlabelTable::class)]
#[Group('Table')]
class DlidlabelTableTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		Factory::$application = new class {
			public function isClient($client)
			{
				return false; // Backend context: onBeforeCheck() only forces user_id to null on the frontend.
			}

			public function getIdentity()
			{
				return new User(0);
			}
		};

		Factory::$container = new class {
			public function get($interface)
			{
				return new class {
					public function loadUserById($id)
					{
						return new User($id);
					}
				};
			}
		};
	}

	protected function tearDown(): void
	{
		Factory::reset();

		parent::tearDown();
	}

	private function newLabel(ScriptedRecordingDatabase $db): DlidlabelTable
	{
		$label = (new ReflectionClass(DlidlabelTable::class))->newInstanceWithoutConstructor();
		$label->setDatabase($db);
		$label->id        = 0;
		$label->title     = '';
		$label->dlid      = '';
		$label->published = null;
		$label->primary   = null;

		return $label;
	}

	private function invokeOnBeforeCheck(DlidlabelTable $label): void
	{
		$ref = new ReflectionMethod(DlidlabelTable::class, 'onBeforeCheck');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		$ref->invoke($label);
	}

	public function testGeneratedDownloadIdIs32LowerCaseHexCharacters(): void
	{
		$db          = new ScriptedRecordingDatabase();
		$db->result  = 0; // No collisions, no existing primary.

		$label          = $this->newLabel($db);
		$label->user_id = 42;

		$this->invokeOnBeforeCheck($label);

		$this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $label->dlid);
	}

	public function testFirstDownloadIdForAUserBecomesThePrimaryLabel(): void
	{
		$db         = new ScriptedRecordingDatabase();
		$db->result = 0; // COUNT(*) queries (primary-exists check, collision check) both report zero.

		$label          = $this->newLabel($db);
		$label->user_id = 42;

		$this->invokeOnBeforeCheck($label);

		$this->assertSame(1, $label->primary);
		$this->assertSame(1, $label->published, 'A primary Download ID is always published.');
		$this->assertSame('_MAIN_', $label->title, 'A primary Download ID has the fixed title _MAIN_.');
	}

	public function testSecondDownloadIdForAUserIsNotPrimary(): void
	{
		$db = new ScriptedRecordingDatabase();
		// First loadResult(): "do you already have a primary?" -> yes (1). Second: dlid-collision check -> no (0).
		$db->resultQueue = [1, 0];

		$label          = $this->newLabel($db);
		$label->user_id = 42;

		$this->invokeOnBeforeCheck($label);

		$this->assertSame(0, $label->primary);
		$this->assertNotSame('_MAIN_', $label->title);
	}

	public function testEmptyUserIdIsRefused(): void
	{
		$db = new ScriptedRecordingDatabase();

		$label          = $this->newLabel($db);
		$label->user_id = 0;

		$this->expectException(RuntimeException::class);

		$this->invokeOnBeforeCheck($label);
	}

	public function testNullUserIdIsRefused(): void
	{
		$db = new ScriptedRecordingDatabase();

		$label          = $this->newLabel($db);
		$label->user_id = null;

		$this->expectException(RuntimeException::class);

		$this->invokeOnBeforeCheck($label);
	}

	public function testDownloadIdRetriesOnCollision(): void
	{
		$db = new ScriptedRecordingDatabase();
		// primary-exists check -> 0 (becomes primary); then the collision loop: collide once, then succeed.
		$db->resultQueue = [0, 1, 0];

		$label          = $this->newLabel($db);
		$label->user_id = 7;

		$this->invokeOnBeforeCheck($label);

		$this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $label->dlid);
		$this->assertSame(1, $label->primary);
	}
}

<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\ARS\UnitTest\Stubs\RecordingDatabase;
use Akeeba\Component\ARS\Administrator\Model\DlidlabelsModel;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Regression coverage for the I2 fix: `getListQuery()`'s frontend title-OR-dlid search built
 * `` `title`LIKE :search `` — missing the space between the quoted identifier and `LIKE` — a SQL
 * syntax error, not an injection (the bound value was still safely parameterised). A frontend search
 * containing a colon (the Download ID `userid:dlid` shape) would throw a DB exception instead of
 * filtering correctly. See `security.md`.
 */
#[CoversClass(DlidlabelsModel::class)]
#[Group('Model')]
class DlidlabelsModelTest extends TestCase
{
	protected function tearDown(): void
	{
		Factory::reset();

		parent::tearDown();
	}

	private function stubFrontendIdentity(int $userId): void
	{
		Factory::$application = new class($userId) {
			public function __construct(private int $userId)
			{
			}

			public function isClient($client)
			{
				return $client === 'site';
			}

			public function getIdentity()
			{
				return new User($this->userId);
			}
		};
	}

	private function buildQuery(string $search): object
	{
		$db = new RecordingDatabase();

		$model = new DlidlabelsModel([], null);
		$model->setDatabase($db);
		$model->setState('filter.search', $search);
		$model->setState('filter.dlid', '');
		$model->setState('filter.published', '');

		$ref = new ReflectionMethod(DlidlabelsModel::class, 'getListQuery');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		return $ref->invoke($model);
	}

	/**
	 * The regression case itself: a frontend search shaped like `otheruser:secret` — where the part
	 * before the colon is non-numeric, so it is NOT treated as a user-ID prefix — reaches the
	 * title-OR-dlid `extendWhere()` branch that carried the missing-space bug.
	 */
	public function testFrontendSearchWithAColonBuildsAWellFormedTitleLikeCondition(): void
	{
		$this->stubFrontendIdentity(7);

		$query = $this->buildQuery('otheruser:secret');

		$this->assertContains('`title` LIKE :search', $query->whereCalls, 'Missing space before LIKE reproduces the I2 bug.');
		$this->assertContains('`dlid` LIKE :dlid', $query->whereCalls);
	}

	public function testFrontendSearchWithNoColonUsesThePlainTitleLikeBranch(): void
	{
		$this->stubFrontendIdentity(7);

		$query = $this->buildQuery('just a title');

		$this->assertContains('`title` LIKE :search', $query->whereCalls);
	}
}

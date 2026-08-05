<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Tests;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\AbstractE2ETestCase;
use Akeeba\ARS\IntegrationTest\Engine\Response;
use Akeeba\ARS\IntegrationTest\Engine\Surfer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Regression for commit 139d02c2, "Hardening: SQL sink defense-in-depth in list models".
 *
 * That commit quoted the ORDER BY identifier with quoteName() (replacing escape(), a value escaper
 * misapplied to an identifier) and constrained the direction to an explicit ASC/DESC whitelist across
 * all eight backend list models; bound the tag filter's IN() list with ParameterType::INTEGER in the
 * Categories and Releases models; and fixed an always-true precedence bug in the API's
 * PopulateModelState int guard. The audit that produced it found no exploitable injection — this
 * class hardens the assertion from "no injection found" to "hostile input still does not misbehave".
 *
 * @since 7.5.0
 */
#[Group('authorisation')]
class ListOrderingInjectionTest extends AbstractE2ETestCase
{
	/**
	 * Hostile values for `filter_order` (the ordering COLUMN). Every one of them, if it reached the
	 * query unquoted, would either break the SQL outright or return more/different rows than the
	 * ordering clause should be able to affect.
	 *
	 * @since 7.5.0
	 */
	private const HOSTILE_ORDER_COLUMNS = [
		'id; DROP TABLE x',
		'id) UNION SELECT',
		'1,(SELECT SLEEP(0))',
		'not_a_real_column',
	];

	/**
	 * @return array<string, array{0: string}>
	 * @since  7.5.0
	 */
	public static function backendListViewProvider(): array
	{
		return [
			'categories'    => ['categories'],
			'releases'      => ['releases'],
			'items'         => ['items'],
			'updatestreams' => ['updatestreams'],
			'logs'          => ['logs'],
		];
	}

	/**
	 * Table names (with the `#__` placeholder) each of the views above ultimately reads from, so the
	 * "no table was dropped or altered" assertion has something concrete to check.
	 *
	 * @since 7.5.0
	 */
	private const VIEW_TABLES = [
		'categories'    => '#__ars_categories',
		'releases'      => '#__ars_releases',
		'items'         => '#__ars_items',
		'updatestreams' => '#__ars_updatestreams',
		'logs'          => '#__ars_log',
	];

	/**
	 * Hostile `filter_order` / `filter_order_Dir` values, and a hostile `list[fullordering]`, do not
	 * produce a 500 or a SQL error, and do not change the table's row count — as manager, over the
	 * real back-end list controller for every list view in the component.
	 *
	 * @since 7.5.0
	 */
	#[DataProvider('backendListViewProvider')]
	public function testHostileOrderingDoesNotBreakTheBackendList(string $view): void
	{
		$manager    = $this->loggedInBackend('manager');
		$table      = self::VIEW_TABLES[$view];
		$beforeRows = (int) $this->db()->value(sprintf('SELECT COUNT(*) FROM `%s`', $table));

		foreach (self::HOSTILE_ORDER_COLUMNS as $payload)
		{
			$response = $manager->get($this->adminUrl([
				'view'             => $view,
				'filter_order'     => $payload,
				'filter_order_Dir' => 'ASC',
			]));

			$this->assertNotABrokenListResponse(
				$response,
				sprintf('filter_order=%s broke the %s list.', $payload, $view)
			);
		}

		// Direction whitelist: an injected direction must not reach the query. A SLEEP() that actually
		// ran would show up as elapsed time, not just a status code.
		$start    = microtime(true);
		$response = $manager->get($this->adminUrl([
			'view'             => $view,
			'filter_order'     => 'id',
			'filter_order_Dir' => 'ASC, (SELECT SLEEP(3))',
		]));
		$elapsed = microtime(true) - $start;

		$this->assertNotABrokenListResponse($response, sprintf('A hostile filter_order_Dir broke the %s list.', $view));
		$this->assertLessThan(
			2.0,
			$elapsed,
			sprintf(
				'The %s list took %.2fs to answer a hostile filter_order_Dir; the SLEEP(3) payload appears to '
				. 'have reached the database.',
				$view,
				$elapsed
			)
		);

		// list[fullordering] is the other request shape Joomla list views accept for ordering.
		$response = $manager->get($this->adminUrl(['view' => $view]), [
			'list' => ['fullordering' => 'id; DROP TABLE x ASC'],
		]);

		$this->assertNotABrokenListResponse($response, sprintf('A hostile list[fullordering] broke the %s list.', $view));

		$afterRows = (int) $this->db()->value(sprintf('SELECT COUNT(*) FROM `%s`', $table));

		$this->assertSame(
			$beforeRows,
			$afterRows,
			sprintf('The row count of %s changed after hostile ordering payloads against the %s list.', $table, $view)
		);
	}

	/**
	 * The tag filter accepts an array of tag IDs. Neither a malformed element nor a perfectly valid
	 * multi-element list may break the Categories or Releases list view.
	 *
	 * Both used to. The multi-tag branch built a DISTINCT subquery, bound its IN() list with
	 * whereIn(…, ParameterType::INTEGER) on the *subquery*, and then inlined that subquery into the
	 * outer query's JOIN by casting it to a string. Bound parameters do not survive that cast —
	 * DatabaseQuery::getBounded() only ever returns the query object's own bindings — so the
	 * `:preparedArray…` placeholders reached the driver unbound and every request died with "No data
	 * supplied for parameters in prepared statement". Filtering by two tags at once, entirely from the
	 * component's own filter bar, was enough; nothing hostile was needed.
	 *
	 * A malformed element reached the same branch by a different route: `filter[tag][]` with two
	 * elements, one of them non-numeric. That made the failure worse than a one-off 500, because the
	 * value is stored via `getUserStateFromRequest()` into that user's session state for the view, so
	 * it kept crashing every subsequent request to the SAME list view in the SAME session — including
	 * ones that never mentioned the tag filter at all — until a valid `filter[tag]` overwrote it.
	 *
	 * So this asserts three things, on both views: valid multi-tag filtering answers 200 and returns
	 * exactly the tagged records, once each; a malformed element is discarded rather than pushed into
	 * the query; and neither leaves the session poisoned for the request that follows.
	 *
	 * @since 7.5.0
	 */
	public function testTagFilterHandlesMalformedAndMultipleValues(): void
	{
		[$tagA, $tagB] = $this->createTemporaryTags();

		$categoryOnlyA = static::$fixtures->categoryId('public');
		$categoryOnlyB = static::$fixtures->categoryId('secret');
		$categoryBoth  = static::$fixtures->categoryId('restricted');
		$releaseOnlyA  = static::$fixtures->releaseId('publicStable');
		$releaseBoth   = static::$fixtures->releaseId('publicBeta');

		$beforeCategories = (int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_categories`');
		$beforeReleases   = (int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_releases`');

		try
		{
			$this->tagContentItem('com_ars.category', $categoryOnlyA, $tagA);
			$this->tagContentItem('com_ars.category', $categoryOnlyB, $tagB);
			$this->tagContentItem('com_ars.category', $categoryBoth, $tagA);
			$this->tagContentItem('com_ars.category', $categoryBoth, $tagB);
			$this->tagContentItem('com_ars.release', $releaseOnlyA, $tagA);
			$this->tagContentItem('com_ars.release', $releaseBoth, $tagA);
			$this->tagContentItem('com_ars.release', $releaseBoth, $tagB);

			// One tag, two tags, and two tags one of which matches nothing: all valid, all must work.
			$this->assertTagFilterSelects('categories', [$tagA], [$categoryOnlyA, $categoryBoth]);
			$this->assertTagFilterSelects('categories', [$tagB], [$categoryOnlyB, $categoryBoth]);
			$this->assertTagFilterSelects(
				'categories',
				[$tagA, $tagB],
				[$categoryOnlyA, $categoryOnlyB, $categoryBoth]
			);
			$this->assertTagFilterSelects('categories', [$tagA, $tagB, 999999], [$categoryOnlyA, $categoryOnlyB, $categoryBoth]);

			$this->assertTagFilterSelects('releases', [$tagA], [$releaseOnlyA, $releaseBoth]);
			$this->assertTagFilterSelects('releases', [$tagA, $tagB], [$releaseOnlyA, $releaseBoth]);

			// A malformed element is dropped; the rest of the filter still applies.
			$this->assertTagFilterSelects('categories', ['DROP TABLE x', $tagB], [$categoryOnlyB, $categoryBoth]);
			$this->assertTagFilterSelects('releases', ['DROP TABLE x', $tagB], [$releaseBoth]);

			// A filter with nothing usable left in it is not applied at all, rather than failing.
			foreach (['categories', 'releases'] as $view)
			{
				foreach ([['DROP TABLE x'], ['DROP TABLE x', 'nonsense'], ['-1', '0']] as $payload)
				{
					$manager  = $this->loggedInBackend('manager');
					$response = $manager->get(
						$this->adminUrl(['view' => $view]),
						['filter' => ['tag' => $payload]]
					);

					$this->assertNotABrokenListResponse(
						$response,
						sprintf('filter[tag]=%s broke the %s list.', json_encode($payload), $view)
					);

					// The same session, one request later, with no tag filter mentioned at all. This is the
					// half that used to keep failing after the crash, because the bad value was persisted.
					$followUp = $manager->get($this->adminUrl(['view' => $view]));

					$this->assertNotABrokenListResponse(
						$followUp,
						sprintf(
							'After filter[tag]=%s, the next request to the %s list in the same session broke; the bad '
							. 'value was persisted into the session state.',
							json_encode($payload),
							$view
						)
					);
				}
			}
		}
		finally
		{
			$this->removeTemporaryTags();
		}

		$this->assertSame(
			$beforeCategories,
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_categories`'),
			'The categories row count changed after hostile tag filter payloads.'
		);
		$this->assertSame(
			$beforeReleases,
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_releases`'),
			'The releases row count changed after hostile tag filter payloads.'
		);
	}

	/**
	 * The same hostile ordering values, sent to the JSON:API list endpoints via `list[ordering]` and
	 * `list[direction]`, do not break the response either. ApiController itself whitelists
	 * list.ordering against the model's filter columns and list.direction against asc/desc before the
	 * model ever sees them (Joomla\CMS\MVC\Controller\ApiController::displayList()), which is the API
	 * analogue of the same defence.
	 *
	 * @since 7.5.0
	 */
	public function testHostileOrderingDoesNotBreakApiListEndpoints(): void
	{
		$token      = static::$fixtures->apiToken('manager');
		$headers    = ['Accept' => 'application/vnd.api+json', 'X-Joomla-Token' => $token];
		$paths      = ['v1/ars/categories', 'v1/ars/releases', 'v1/ars/items'];
		$beforeRows = [
			'v1/ars/categories' => (int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_categories`'),
			'v1/ars/releases'   => (int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_releases`'),
			'v1/ars/items'      => (int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_items`'),
		];

		foreach ($paths as $path)
		{
			foreach (self::HOSTILE_ORDER_COLUMNS as $payload)
			{
				$surfer   = new Surfer(static::$config->getSiteUrl());
				$response = $surfer->get(
					$this->apiUrl($path),
					['list' => ['ordering' => $payload, 'direction' => 'ASC']],
					$headers
				);

				$this->assertStatus(200, $response, sprintf('list[ordering]=%s broke %s.', $payload, $path));
				$this->assertApiBodyIsWellFormed($response, $path, $payload);
			}

			$start    = microtime(true);
			$surfer   = new Surfer(static::$config->getSiteUrl());
			$response = $surfer->get(
				$this->apiUrl($path),
				['list' => ['ordering' => 'id', 'direction' => 'ASC, (SELECT SLEEP(3))']],
				$headers
			);
			$elapsed = microtime(true) - $start;

			$this->assertStatus(200, $response, sprintf('A hostile list[direction] broke %s.', $path));
			$this->assertLessThan(
				2.0,
				$elapsed,
				sprintf('%s took %.2fs to answer a hostile list[direction]; the SLEEP(3) appears to have run.', $path, $elapsed)
			);
		}

		$this->assertSame(
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_categories`'),
			$beforeRows['v1/ars/categories'],
			'The categories row count changed after hostile API ordering payloads.'
		);
		$this->assertSame(
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_releases`'),
			$beforeRows['v1/ars/releases'],
			'The releases row count changed after hostile API ordering payloads.'
		);
		$this->assertSame(
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_items`'),
			$beforeRows['v1/ars/items'],
			'The items row count changed after hostile API ordering payloads.'
		);
	}

	// -----------------------------------------------------------------------
	// Helpers.
	// -----------------------------------------------------------------------

	/**
	 * IDs of the tags created by createTemporaryTags(), so they can be removed again.
	 *
	 * @var   int[]
	 * @since 7.5.0
	 */
	private array $temporaryTagIds = [];

	/**
	 * Create two published tags to filter by.
	 *
	 * The fixtures deliberately ship none — ARS categories and releases are taggable, but nothing in
	 * the rest of the suite needs a tag — so this test makes its own and removes them again.
	 *
	 * @return  int[]  The two tag IDs.
	 * @since   7.5.0
	 */
	private function createTemporaryTags(): array
	{
		$db  = $this->db();
		$now = gmdate('Y-m-d H:i:s');

		foreach (['e2e-tag-alpha', 'e2e-tag-beta'] as $alias)
		{
			$this->temporaryTagIds[] = $db->insert(
				'#__tags',
				[
					'parent_id'      => 1,
					'lft'            => 0,
					'rgt'            => 0,
					'level'          => 1,
					'path'           => $alias,
					'title'          => $alias,
					'alias'          => $alias,
					'note'           => '',
					'description'    => '',
					'published'      => 1,
					'access'         => 1,
					'params'         => '{}',
					'metadesc'       => '',
					'metakey'        => '',
					'metadata'       => '{}',
					'created_user_id' => 0,
					'created_time'   => $now,
					'modified_user_id' => 0,
					'modified_time'  => $now,
					'images'         => '',
					'urls'           => '',
					'hits'           => 0,
					'language'       => '*',
					'version'        => 1,
				]
			);
		}

		return $this->temporaryTagIds;
	}

	/**
	 * Tag one content item with one of the temporary tags.
	 *
	 * @param   string  $typeAlias  The UCM type alias, e.g. `com_ars.category`.
	 * @param   int     $itemId     The tagged record's ID.
	 * @param   int     $tagId      The tag's ID.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	private function tagContentItem(string $typeAlias, int $itemId, int $tagId): void
	{
		$this->db()->insert(
			'#__contentitem_tag_map',
			[
				'type_alias'      => $typeAlias,
				'core_content_id' => 0,
				'content_item_id' => $itemId,
				'tag_id'          => $tagId,
				'tag_date'        => gmdate('Y-m-d H:i:s'),
				// The map's unique key is (type_id, content_item_id, tag_id), so the two aliases need
				// distinct type IDs or a category and a release sharing an ID would collide. ARS filters
				// on type_alias alone, so any distinct pair will do.
				'type_id'         => $typeAlias === 'com_ars.category' ? 1 : 2,
			]
		);
	}

	/**
	 * Remove the temporary tags and every mapping made to them.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	private function removeTemporaryTags(): void
	{
		if (empty($this->temporaryTagIds))
		{
			return;
		}

		$idList = implode(',', array_map('intval', $this->temporaryTagIds));

		$this->db()->query(sprintf('DELETE FROM `#__contentitem_tag_map` WHERE `tag_id` IN (%s)', $idList));
		$this->db()->query(sprintf('DELETE FROM `#__tags` WHERE `id` IN (%s)', $idList));

		$this->temporaryTagIds = [];
	}

	/**
	 * Assert that a back-end list view, filtered by the given tags, shows exactly the given records.
	 *
	 * Reads the row IDs off each row's edit link, which is also how it catches a record returned
	 * twice: a tag filter joining a tag map without collapsing the duplicates would list a record
	 * carrying two of the filtered tags once per tag. (The `cid[]` checkbox would be the obvious
	 * marker, but `grid.id` omits its value for a checked-out record, so a stale checkout in the
	 * fixtures would silently drop rows from the comparison.)
	 *
	 * @param   string             $view      `categories` or `releases`.
	 * @param   array              $tags      The `filter[tag]` payload, valid IDs or not.
	 * @param   int[]              $expected  The record IDs the list must show, in any order.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	private function assertTagFilterSelects(string $view, array $tags, array $expected): void
	{
		$manager  = $this->loggedInBackend('manager');
		$response = $manager->get($this->adminUrl(['view' => $view]), ['filter' => ['tag' => $tags]]);
		$context  = sprintf('%s list, filter[tag]=%s', $view, json_encode($tags));

		$this->assertNotABrokenListResponse($response, $context . ' broke the list.');

		$editTask = $view === 'categories' ? 'category\.edit' : 'release\.edit';

		preg_match_all('/task=' . $editTask . '&(?:amp;)?id=(\d+)/', $response->body, $matches);

		$actual = array_map('intval', $matches[1]);

		sort($actual);
		$expectedSorted = array_map('intval', $expected);
		sort($expectedSorted);

		$this->assertSame(
			$expectedSorted,
			$actual,
			sprintf('%s did not list exactly the tagged records. %s', $context, $response->summary())
		);
	}

	/**
	 * Assert that a back-end list response is neither an application error page nor a page whose body
	 * carries a raw SQL error string.
	 *
	 * @since 7.5.0
	 */
	private function assertNotABrokenListResponse(Response $response, string $message): void
	{
		$this->assertNotSame(500, $response->code, trim($message . "\n" . $response->summary()));

		$needles = ['sqlstate', 'syntax error', 'sql error', 'unknown column', 'you have an error in your sql'];
		$body    = strtolower($response->body);

		foreach ($needles as $needle)
		{
			$this->assertStringNotContainsString(
				$needle,
				$body,
				trim($message . " Response body contains a SQL error string (\"$needle\").")
			);
		}
	}

	/**
	 * Assert that an API list response is genuinely a well-formed JSON:API list body, not an error
	 * masquerading as 200.
	 *
	 * @since 7.5.0
	 */
	private function assertApiBodyIsWellFormed(Response $response, string $path, string $payload): void
	{
		$json = $response->json();

		$this->assertIsArray(
			$json,
			sprintf('list[ordering]=%s made %s return a non-JSON body.', $payload, $path)
		);
		$this->assertArrayHasKey(
			'data',
			$json,
			sprintf('list[ordering]=%s made %s return a body with no "data" key.', $payload, $path)
		);
		$this->assertIsArray(
			$json['data'],
			sprintf('list[ordering]=%s made %s return "data" that is not a list.', $payload, $path)
		);
	}
}

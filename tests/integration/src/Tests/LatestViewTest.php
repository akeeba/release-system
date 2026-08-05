<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Tests;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\AbstractE2ETestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Regression for commit `409d70bf` ("Fix Latest view error page when no release is visible"). Before
 * that fix, `Latest\HtmlView::onBeforeMain()` passed the ids of every release the current user can see
 * to `ItemsModel` as a `filter.release_id` array. With zero visible releases that array is empty, and
 * `ItemsModel` forwarded it straight to `whereIn()`, producing a literal `IN ()` SQL clause — a syntax
 * error, so the Latest view 500'd for precisely the user for whom it should have shown an empty page.
 *
 * @since 7.5.0
 */
#[Group('regression')]
class LatestViewTest extends AbstractE2ETestCase
{
	/**
	 * A guest — who can see the public category's releases — gets a normal 200 from the Latest view.
	 * This is the ordinary, "some releases visible" path; it establishes that the view is not simply
	 * broken outright before the more interesting "nothing visible" case below is examined.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testGuestGetsTwoHundredFromTheLatestView(): void
	{
		$response = $this->guest()->get($this->siteUrl(['view' => 'latest']));

		$this->assertStatus(200, $response, 'The Latest view did not render for a guest.');
		$this->assertBodyNotContains(
			'stopped responding',
			$response,
			'The Latest view rendered an error page fragment even though it reported HTTP 200.'
		);
	}

	/**
	 * Every layout the Latest view ships (`default`, `generic`, `category`, `item` — see
	 * `component/frontend/tmpl/latest/`) renders 200 for a guest, confirmed empirically request by
	 * request rather than assumed from the view listing every one of these as a `<?php` template file.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testEveryLatestLayoutRendersForAGuest(): void
	{
		foreach (['default', 'generic', 'category', 'item'] as $layout)
		{
			$response = $this->guest()->get($this->siteUrl(['view' => 'latest', 'layout' => $layout]));

			$this->assertStatus(200, $response, sprintf('The Latest view\'s "%s" layout did not render for a guest.', $layout));
		}
	}

	/**
	 * The `client` account — logged in, but not a subscriber — also gets 200.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testClientGetsTwoHundredFromTheLatestView(): void
	{
		$response = $this->loggedIn('client')->get($this->siteUrl(['view' => 'latest']));

		$this->assertStatus(200, $response, 'The Latest view did not render for the client account.');
		$this->assertBodyNotContains(
			'stopped responding',
			$response,
			'The Latest view rendered an error page fragment even though it reported HTTP 200.'
		);
	}

	/**
	 * Neither a guest nor the client account sees a restricted, secret or unpublished release's title,
	 * or any file sentinel that does not belong to a category they can see.
	 *
	 * The `restricted` category's title ("E2E Subscriber Downloads") is checked with a regular
	 * expression rather than `assertBodyNotContains()`, because it is a literal string PREFIX of
	 * `restrictedLinked`'s own title, "E2E Subscriber Downloads (Linked)" — and `restrictedLinked`
	 * carries `show_unauth_links = 1`, so it legitimately shows its title to a guest even though the
	 * download itself is still refused. A plain substring check would misreport that legitimate,
	 * by-design visibility as a leak of the (non-linked) restricted category.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testLatestViewDoesNotLeakRestrictedContent(): void
	{
		foreach (['guest' => $this->guest(), 'client' => $this->loggedIn('client')] as $label => $surfer)
		{
			$response = $surfer->get($this->siteUrl(['view' => 'latest']));

			$this->assertDoesNotMatchRegularExpression(
				'/E2E Subscriber Downloads(?!\s*\(Linked\))/',
				$response->body,
				sprintf('The Latest view leaked the restricted category title to a %s.', $label)
			);

			foreach (['E2E Secret Downloads', 'E2E Unpublished Downloads'] as $title)
			{
				$this->assertBodyNotContains(
					$title,
					$response,
					sprintf('The Latest view leaked the "%s" category title to a %s.', $title, $label)
				);
			}

			foreach (['restrictedFile', 'secretFile', 'unpublishedCatFile', 'unpublishedReleaseFile'] as $itemKey)
			{
				$this->assertBodyNotContains(
					static::$fixtures->file($itemKey)['sentinel'],
					$response,
					sprintf('The Latest view leaked the "%s" file sentinel to a %s.', $itemKey, $label)
				);
			}
		}
	}

	/**
	 * The actual regression: a user for whom NO release is visible at all must still get 200, not a
	 * database error.
	 *
	 * This state cannot be reached read-only with the provisioned fixtures. `Latest\HtmlView` and
	 * `LatestController::main()` take no `category_id` (or any other) filter parameter at all — the
	 * page unconditionally shows every published, access-permitted category and release, with no way to
	 * narrow it from the query string (confirmed by reading `LatestController::main()`, which never
	 * reads such an input, and empirically: passing `category_id` to `view=latest` has no visible
	 * effect on the response). Every provisioned account, including `client`, holds the Public view
	 * level, and the `public` category (access = Public) always carries at least one published,
	 * visible release, so no combination of provisioned account and request reaches "zero visible
	 * releases" without mutating a fixture (unpublishing the public category or its releases) — which
	 * this suite's read-only convention rules out.
	 *
	 * The closest reachable proxy is asserted instead: fetching the Latest view for EVERY provisioned
	 * non-privileged account is 200 with no error-page fragment, which is what
	 * {@see testGuestGetsTwoHundredFromTheLatestView()} and {@see testClientGetsTwoHundredFromTheLatestView()}
	 * already do. This test exists to record, in one place, why the literal "zero releases" case is not
	 * separately exercised here.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testTrueZeroVisibleReleasesStateIsNotReachableReadOnly(): void
	{
		$this->markTestSkipped(
			'The "no release is visible at all" state that commit 409d70bf fixed cannot be reached read-only: '
			. 'the Latest view takes no category/access-narrowing query parameter, and every provisioned account '
			. 'holds the Public view level with at least one visible release in the public category. Reaching it '
			. 'would require unpublishing a fixture, which this suite\'s tests must not do. '
			. 'testGuestGetsTwoHundredFromTheLatestView() and testClientGetsTwoHundredFromTheLatestView() are the '
			. 'closest reachable regression coverage: they confirm the view still renders normally post-fix.'
		);
	}
}

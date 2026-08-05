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
 * Browsing categories, releases and items on the front end.
 *
 * `view=releases` and `view=items` take the target id as `category_id` / `release_id` respectively —
 * confirmed empirically: `id=` on either route 404s regardless of actor, because
 * `ReleasesController::onBeforeDisplay()` and `ItemsController` read `category_id`/`release_id` from
 * the request, not `id`.
 *
 * @since 7.5.0
 */
#[Group('authorisation')]
class CategoryBrowsingTest extends AbstractE2ETestCase
{
	/**
	 * A guest sees the public category and not the restricted, secret or unpublished ones.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testGuestSeesOnlyThePublicCategory(): void
	{
		$response = $this->guest()->get($this->siteUrl(['view' => 'categories', 'layout' => 'repository']));

		$this->assertStatus(200, $response, 'The category list did not render for a guest.');
		$this->assertBodyContains('E2E Public Downloads', $response, 'A guest cannot see the public category.');

		// "E2E Subscriber Downloads" is a literal string PREFIX of restrictedLinked's own title, "E2E
		// Subscriber Downloads (Linked)" — and restrictedLinked carries show_unauth_links = 1, so its
		// title legitimately appears here even for a guest. A plain substring check would misreport
		// that as a leak of the (non-linked) restricted category, so this uses a negative lookahead
		// instead.
		$this->assertDoesNotMatchRegularExpression(
			'/E2E Subscriber Downloads(?!\s*\(Linked\))/',
			$response->body,
			'A guest can see the restricted category title.'
		);

		foreach (['E2E Secret Downloads', 'E2E Unpublished Downloads'] as $title)
		{
			$this->assertBodyNotContains($title, $response, sprintf('A guest can see the "%s" category title.', $title));
		}

		foreach (['restrictedFile', 'secretFile', 'unpublishedCatFile'] as $itemKey)
		{
			$this->assertBodyNotContains(
				static::$fixtures->file($itemKey)['sentinel'],
				$response,
				sprintf('A guest\'s category list leaks the "%s" file sentinel.', $itemKey)
			);
		}
	}

	/**
	 * A subscriber sees the restricted category, in addition to the public one.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testSubscriberSeesTheRestrictedCategory(): void
	{
		$response = $this->loggedIn('subscriber')->get($this->siteUrl(['view' => 'categories', 'layout' => 'repository']));

		$this->assertStatus(200, $response, 'The category list did not render for a subscriber.');
		$this->assertBodyContains('E2E Public Downloads', $response, 'A subscriber cannot see the public category.');
		$this->assertBodyContains('E2E Subscriber Downloads', $response, 'A subscriber cannot see the restricted category.');
		$this->assertBodyNotContains('E2E Secret Downloads', $response, 'A subscriber can see the secret category title.');
	}

	/**
	 * `view=releases` for the secret category is refused for everyone, including the manager — nobody
	 * holds the Secret view level.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testReleasesForTheSecretCategoryAreRefusedForEveryone(): void
	{
		$secretCategoryId = static::$fixtures->categoryId('secret');

		foreach (['guest' => $this->guest(), 'client' => $this->loggedIn('client'), 'subscriber' => $this->loggedIn('subscriber'), 'manager' => $this->loggedIn('manager')] as $label => $surfer)
		{
			$response = $surfer->get($this->siteUrl(['view' => 'releases', 'category_id' => $secretCategoryId]));

			$this->assertRefused($surfer, $response, sprintf('A %s could browse the releases of the secret category.', $label));
			$this->assertBodyNotContains(
				static::$fixtures->file('secretFile')['sentinel'],
				$response,
				sprintf('A %s was refused the secret category releases, but a file sentinel leaked into the response.', $label)
			);
		}
	}

	/**
	 * A guest CAN browse the restricted category's releases, and the public category's, as the positive
	 * control that proves the refusal above is actually checking something rather than everything
	 * always being refused.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testGuestCanBrowsePublicCategoryReleasesAndASubscriberCanBrowseTheRestrictedOne(): void
	{
		$publicResponse = $this->guest()->get($this->siteUrl([
			'view'        => 'releases',
			'category_id' => static::$fixtures->categoryId('public'),
		]));

		$this->assertStatus(200, $publicResponse, 'A guest could not browse the public category releases.');

		$restrictedAsGuest = $this->guest()->get($this->siteUrl([
			'view'        => 'releases',
			'category_id' => static::$fixtures->categoryId('restricted'),
		]));

		$this->assertRefused($this->guest(), $restrictedAsGuest, 'A guest could browse the restricted category releases.');

		$restrictedAsSubscriber = $this->loggedIn('subscriber')->get($this->siteUrl([
			'view'        => 'releases',
			'category_id' => static::$fixtures->categoryId('restricted'),
		]));

		$this->assertStatus(200, $restrictedAsSubscriber, 'A subscriber could not browse the restricted category releases.');
	}

	/**
	 * `view=items` for a release follows the same access rules: a guest can browse the public
	 * release's items, and is refused for the restricted release's items, without any file sentinel
	 * leaking into the refusal response.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testItemsViewFollowsTheReleasesAccessControl(): void
	{
		$guest = $this->guest();

		$publicResponse = $guest->get($this->siteUrl([
			'view'       => 'items',
			'release_id' => static::$fixtures->releaseId('publicStable'),
		]));

		$this->assertStatus(200, $publicResponse, "A guest could not browse the public release's items.");

		$restrictedResponse = $guest->get($this->siteUrl([
			'view'       => 'items',
			'release_id' => static::$fixtures->releaseId('restrictedStable'),
		]));

		$this->assertRefused($guest, $restrictedResponse, "A guest could browse the restricted release's items.");
		$this->assertBodyNotContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$restrictedResponse,
			'A guest was refused the restricted release items, but a file sentinel leaked into the response.'
		);
	}

	/**
	 * `restrictedLinked` carries `show_unauth_links = 1` and a `redirect_unauth` target
	 * (`index.php?option=com_users&view=login`, per the fixtures). A guest denied access is redirected
	 * there rather than shown a 403, and the redirect target must stay on this site — the open-redirect
	 * guard `assertRedirectIsInternal()` exists precisely to catch a `redirect_unauth` value (or a bug
	 * in how it is routed) that could send a user off-site.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testRestrictedLinkedCategoryRedirectsAGuestInsteadOfRefusingOutright(): void
	{
		$guest    = $this->guest();
		$response = $guest->get($this->siteUrl([
			'view'        => 'releases',
			'category_id' => static::$fixtures->categoryId('restrictedLinked'),
		]));

		$this->assertTrue($response->isRedirect(), 'A guest denied the restrictedLinked category was not redirected at all.');
		$this->assertRedirectIsInternal($response, 'The restrictedLinked redirect_unauth target left the site.');
	}

	/**
	 * REGRESSION TEST for a confirmed, now fixed bug: `index.php?option=com_ars&view=categories` with NO
	 * `layout` query parameter used to return HTTP 500 ("Layout default not found"), while an explicit,
	 * INVALID layout (e.g. `layout=bogus`) was correctly normalised to `repository` and returned 200.
	 *
	 * `CategoriesController::onBeforeDisplay()`'s whitelist guard used to be:
	 *
	 *     if (!in_array($this->input->get('layout', 'repository'), ['normal', 'bleedingedge', 'repository']))
	 *     {
	 *         $this->input->set('layout', 'repository');
	 *     }
	 *
	 * `$this->input->get('layout', 'repository')` returns the DEFAULT `'repository'` whenever `layout`
	 * is absent, which already satisfies the whitelist — so the `in_array()` check passed and
	 * `$this->input->set('layout', ...)` was never reached. The request's `layout` input stayed unset,
	 * and the view fell through to a `default` layout that does not exist in
	 * `component/frontend/tmpl/categories/` (only `normal`, `bleedingedge` and `repository` do). The
	 * guard now reads the input with an EMPTY default, which fails the whitelist and normalises.
	 *
	 * The bleeding edge category is the discriminator between the layouts: `repository` renders both the
	 * normal and the bleeding edge sections, whereas `normal` renders only the former. Asserting on it
	 * proves the fallback landed on `repository` specifically, not merely on some layout that exists.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testCategoriesViewWithNoLayoutParameterDefaultsToRepository(): void
	{
		$response = $this->guest()->get($this->siteUrl(['view' => 'categories']));

		$this->assertStatus(200, $response, 'The category list with no layout parameter did not render.');
		$this->assertBodyContains('E2E Public Downloads', $response, 'The category list with no layout parameter did not show the public category.');
		$this->assertBodyContains('E2E Bleeding Edge', $response, 'The category list with no layout parameter did not fall back to the repository layout; the bleeding edge section is missing.');
	}

	/**
	 * An explicit but INVALID layout is correctly normalised to `repository` and renders 200 — the
	 * positive control for the bug documented above, proving the whitelist guard works when `layout` is
	 * present at all, and only misbehaves when it is absent.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testCategoriesViewWithAnInvalidLayoutFallsBackToRepository(): void
	{
		$response = $this->guest()->get($this->siteUrl(['view' => 'categories', 'layout' => 'not-a-real-layout']));

		$this->assertStatus(200, $response, 'An invalid layout value was not normalised to a working layout.');
		$this->assertBodyContains('E2E Public Downloads', $response, 'The normalised category list did not show the public category.');
	}
}

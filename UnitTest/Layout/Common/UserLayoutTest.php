<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Layout\Common;

defined('_JEXEC') or die;

use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for the L8 fix: `layout/akeeba/ars/common/user.php` (shared by the backend Logs
 * and Download ID labels lists) echoed `$name`/`$username`/`$email` and built an `href` from `$link`
 * with no escaping at all. Current call sites only pass a numeric-ID `[USER_ID]` link template, so
 * attribute breakout wasn't reachable today, and Joomla's default `string` save filter strips tags from
 * the user's Name field — but this is a shared, reusable layout with a documented
 * `[NAME]`/`[EMAIL]`/`[USERNAME]` substitution feature for `$link`, so a future caller could introduce
 * one, and the text-node echoes had no defence-in-depth regardless. See `security.md`.
 *
 * This is a plain procedural layout file with no `$this` dependency (layouts are rendered standalone,
 * unlike a view template) and no database/application dependency either — it is `include`d directly with
 * `$displayData` set, and its output captured via output buffering, exactly as Joomla's own
 * `LayoutHelper::render()` would invoke it.
 */
class UserLayoutTest extends TestCase
{
	private function layoutPath(): string
	{
		return \dirname(__DIR__, 3) . '/component/backend/layout/akeeba/ars/common/user.php';
	}

	private function render(array $displayData): string
	{
		$path = $this->layoutPath();

		$render = static function () use ($path, $displayData) {
			ob_start();
			include $path;

			return ob_get_clean();
		};

		return $render();
	}

	public function testTheLayoutStillExists(): void
	{
		self::assertFileExists($this->layoutPath());
	}

	/**
	 * Note: the layout only ever echoes `$name` nested inside the `$showUsername` block (that's the
	 * template's existing, slightly surprising structure — `$showName` alone does not gate it), so
	 * `username` must be non-empty and `showUsername` true for `name` to render at all.
	 */
	public function testALegitimateNameIsDisplayedUnchanged(): void
	{
		$html = $this->render([
			'name'         => 'Jane Doe',
			'username'     => 'jane',
			'showUsername' => true,
			'showEmail'    => false,
			'showGravatar' => false,
		]);

		self::assertStringContainsString('Jane Doe', $html);
	}

	/**
	 * The regression case itself: a `<script>` tag in the displayed name must not survive into the
	 * rendered HTML verbatim.
	 */
	public function testAMaliciousNameIsEscapedNotRenderedAsHtml(): void
	{
		$html = $this->render([
			'name'         => '<script>alert(1)</script>',
			'username'     => 'jane',
			'showUsername' => true,
			'showEmail'    => false,
			'showGravatar' => false,
		]);

		self::assertStringNotContainsString('<script>alert(1)</script>', $html);
		self::assertStringContainsString('&lt;script&gt;', $html);
	}

	public function testAMaliciousUsernameIsEscaped(): void
	{
		$html = $this->render([
			'username'     => '<img src=x onerror=alert(1)>',
			'showName'     => false,
			'showEmail'    => false,
			'showGravatar' => false,
		]);

		self::assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
	}

	public function testAMaliciousEmailIsEscaped(): void
	{
		$html = $this->render([
			'email'        => '"><script>alert(1)</script>',
			'showUsername' => false,
			'showName'     => false,
			'showGravatar' => false,
		]);

		self::assertStringNotContainsString('<script>alert(1)</script>', $html);
	}

	/**
	 * `$link`'s `[NAME]`/`[USERNAME]`/`[EMAIL]` substitution feature means the built `href` value is
	 * just as attacker-influenced as the text nodes — a quote character in any substituted field must
	 * not be able to break out of the `href="..."` attribute.
	 */
	public function testASubstitutedValueCannotBreakOutOfTheHrefAttribute(): void
	{
		$html = $this->render([
			'name'         => 'Jane',
			'username'     => 'jane',
			'user_id'      => 5,
			'showLink'     => true,
			'showUsername' => true,
			'showEmail'    => false,
			'showGravatar' => false,
			'link'         => 'index.php?option=com_users&task=user.edit&name=[NAME]" onmouseover="alert(1)',
		]);

		self::assertStringNotContainsString('onmouseover="alert(1)"', $html);
		self::assertMatchesRegularExpression('/href="[^"]*"/', $html, 'The href attribute must still be a single, well-formed quoted attribute.');
	}
}

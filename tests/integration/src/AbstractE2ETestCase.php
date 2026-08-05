<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\Engine\Configuration;
use Akeeba\ARS\IntegrationTest\Engine\ContainerCli;
use Akeeba\ARS\IntegrationTest\Engine\Database;
use Akeeba\ARS\IntegrationTest\Engine\JoomlaSession;
use Akeeba\ARS\IntegrationTest\Engine\Mailpit;
use Akeeba\ARS\IntegrationTest\Engine\Response;
use Akeeba\ARS\IntegrationTest\Engine\Surfer;
use PHPUnit\Framework\TestCase;

/**
 * Base class for the end-to-end tests.
 *
 * Every test here drives a real HTTP request against a real Joomla site with a real session. The
 * assertions that matter are the negative ones: this suite exists because ARS previously had no way
 * to assert that a request is REFUSED, and a test that only walks the happy path has not done the
 * job it was written for.
 *
 * @since 7.5.0
 */
abstract class AbstractE2ETestCase extends TestCase
{
	/**
	 * The suite configuration.
	 *
	 * @var   Configuration
	 * @since 7.5.0
	 */
	protected static Configuration $config;

	/**
	 * The fixtures.
	 *
	 * @var   SiteProvisioner
	 * @since 7.5.0
	 */
	protected static SiteProvisioner $fixtures;

	/**
	 * Surfers created during a test, keyed by role, so each test starts from a clean session.
	 *
	 * Protected, not private: subclasses may memoise their own actors alongside the ones defined
	 * here (see {@see guest()}, {@see loggedIn()}, {@see superUser()}, {@see loggedInBackend()}).
	 *
	 * @var   array<string, Surfer>
	 * @since 7.5.0
	 */
	protected array $surfers = [];

	/**
	 * The login helper.
	 *
	 * @var   JoomlaSession
	 * @since 7.5.0
	 */
	protected JoomlaSession $session;

	/**
	 * Set up the shared configuration and fixtures.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		static::$config   = Configuration::getInstance();
		static::$fixtures = SiteProvisioner::getInstance();
	}

	/**
	 * Set up a test.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->session = new JoomlaSession();
	}

	/**
	 * Tear down a test.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	protected function tearDown(): void
	{
		$this->surfers = [];

		parent::tearDown();
	}

	/**
	 * Put the fixtures back the way they started.
	 *
	 * Call this from a test that mutates release data, so the next test is not asserting against
	 * this one's leftovers.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	protected function resetFixtures(): void
	{
		static::$fixtures->reset();
	}

	/**
	 * A logged-out surfer.
	 *
	 * @return  Surfer
	 * @since   7.5.0
	 */
	protected function guest(): Surfer
	{
		return $this->surfers['__guest'] ??= new Surfer(static::$config->getSiteUrl());
	}

	/**
	 * A surfer logged into the front-end as one of the provisioned accounts.
	 *
	 * @param   string  $role  A role from the fixture manifest, e.g. 'manager', 'downloader', 'owner'.
	 *
	 * @return  Surfer
	 * @since   7.5.0
	 */
	protected function loggedIn(string $role): Surfer
	{
		if (isset($this->surfers[$role]))
		{
			return $this->surfers[$role];
		}

		$surfer = new Surfer(static::$config->getSiteUrl());

		$this->session->loginFrontend(
			$surfer,
			static::$fixtures->username($role),
			static::$config->getUserPassword()
		);

		return $this->surfers[$role] = $surfer;
	}

	/**
	 * A surfer logged into the back-end as the Super User.
	 *
	 * @return  Surfer
	 * @since   7.5.0
	 */
	protected function superUser(): Surfer
	{
		if (isset($this->surfers['__super']))
		{
			return $this->surfers['__super'];
		}

		[$username, $password] = static::$config->getAdminCredentials();

		$surfer = new Surfer(static::$config->getSiteUrl());
		$this->session->loginBackend($surfer, $username, $password);

		return $this->surfers['__super'] = $surfer;
	}

	/**
	 * A surfer logged into the back-end as one of the provisioned accounts.
	 *
	 * Only accounts holding core.login.admin can do this; for ARS that means the Super User and
	 * whoever else you have deliberately granted it to.
	 *
	 * @param   string  $role  A role from the fixture manifest.
	 *
	 * @return  Surfer
	 * @since   7.5.0
	 */
	protected function loggedInBackend(string $role): Surfer
	{
		$key = '__admin_' . $role;

		if (isset($this->surfers[$key]))
		{
			return $this->surfers[$key];
		}

		$surfer = new Surfer(static::$config->getSiteUrl());

		$this->session->loginBackend(
			$surfer,
			static::$fixtures->username($role),
			static::$config->getUserPassword()
		);

		return $this->surfers[$key] = $surfer;
	}

	/**
	 * The site's database.
	 *
	 * @return  Database
	 * @since   7.5.0
	 */
	protected function db(): Database
	{
		static $db = null;

		return $db ??= new Database(static::$config);
	}

	/**
	 * The outbound mail sink.
	 *
	 * @return  Mailpit
	 * @since   7.5.0
	 */
	protected function mailpit(): Mailpit
	{
		static $mailpit = null;

		return $mailpit ??= new Mailpit(static::$config->getMailpitUrl());
	}

	/**
	 * Joomla's console application, inside the site's container.
	 *
	 * @return  ContainerCli
	 * @since   7.5.0
	 */
	protected function cli(): ContainerCli
	{
		static $cli = null;

		return $cli ??= new ContainerCli(static::$config);
	}

	/**
	 * Build a front-end ARS URL.
	 *
	 * @param   array  $query  Query parameters, merged over option=com_ars.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	protected function siteUrl(array $query = []): string
	{
		return 'index.php?' . http_build_query(array_merge(['option' => 'com_ars'], $query));
	}

	/**
	 * Build a back-end ARS URL.
	 *
	 * @param   array  $query  Query parameters, merged over option=com_ars.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	protected function adminUrl(array $query = []): string
	{
		return 'administrator/index.php?' . http_build_query(array_merge(['option' => 'com_ars'], $query));
	}

	/**
	 * Build a REST API URL.
	 *
	 * @param   string  $path  The path under the API's `index.php`, e.g. 'v1/releases'.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	protected function apiUrl(string $path): string
	{
		return 'api/index.php/' . ltrim($path, '/');
	}

	// -----------------------------------------------------------------------
	// Assertions.
	//
	// Each carries the response summary into the failure message. A bare
	// "failed asserting that 200 matches 403" tells you nothing about which
	// of the four moving parts (session, ACL fixture, route, controller) broke.
	// -----------------------------------------------------------------------

	/**
	 * Assert that the response has a given status code.
	 *
	 * @param   int       $expected  The expected status code.
	 * @param   Response  $response  The response.
	 * @param   string    $message   Extra context.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	protected function assertStatus(int $expected, Response $response, string $message = ''): void
	{
		$this->assertSame(
			$expected,
			$response->code,
			trim($message . "\n" . $response->summary())
		);
	}

	/**
	 * Assert that the request was refused.
	 *
	 * Joomla refuses in more than one shape and all of them count:
	 *
	 *   - an error page — 401, 403 or 404 from an uncaught exception carrying that code;
	 *   - a redirect to the login page, or to the category's redirect_unauth target, which is what
	 *     ControllerCRIAccessTrait::accessControlFor() does with a guest when show_unauth_links is on;
	 *   - a redirect carrying a message. This is the important one and it is easy to get wrong:
	 *     BaseController::checkToken() does NOT throw on a bad token. It enqueues
	 *     JINVALID_TOKEN_NOTICE and redirects to the referrer (index.php when there is none). So an
	 *     anti-CSRF refusal looks like an ordinary 303, and the only way to see the refusal is to
	 *     follow it WITH THE SAME SURFER — the message lives in that session, and a fresh surfer
	 *     would find an empty queue and read it as success.
	 *
	 * For a request that would change state, this assertion is necessary but not sufficient: also
	 * assert that the change did not happen. That is the claim that actually matters, and unlike
	 * message text it cannot drift with a language string.
	 *
	 * @param   Surfer    $surfer    The surfer that made the request, whose session holds any message.
	 * @param   Response  $response  The response.
	 * @param   string    $message   Extra context.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	protected function assertRefused(Surfer $surfer, Response $response, string $message = ''): void
	{
		$context = trim($message . "\n" . $response->summary());

		if (in_array($response->code, [401, 403, 404], true))
		{
			$this->assertTrue(true);

			return;
		}

		if ($response->isRedirect())
		{
			$location = (string) $response->getLocation();

			if (stripos($location, 'com_users') !== false && stripos($location, 'login') !== false)
			{
				$this->assertTrue(true);

				return;
			}

			$landing = $surfer->get($location);

			$this->assertTrue(
				$this->bodyLooksLikeRefusal($landing->body),
				"The request redirected, but the page it redirected to carries no refusal message,\n"
				. "so this looks like the action succeeded.\n" . $context
			);

			return;
		}

		if ($response->code === 200 && $this->bodyLooksLikeRefusal($response->body))
		{
			$this->assertTrue(true);

			return;
		}

		$this->fail("Expected the request to be refused, but it was not.\n" . $context);
	}

	/**
	 * Assert that a Location header does not point at another host.
	 *
	 * @param   Response  $response  The response.
	 * @param   string    $message   Extra context.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	protected function assertRedirectIsInternal(Response $response, string $message = ''): void
	{
		$location = $response->getLocation();

		$this->assertNotNull(
			$location,
			trim("Expected a redirect, but there was no Location header.\n" . $message . "\n" . $response->summary())
		);

		$host     = parse_url($location, PHP_URL_HOST);
		$siteHost = parse_url(static::$config->getSiteUrl(), PHP_URL_HOST);

		if ($host === null)
		{
			// A relative Location cannot leave the site.
			$this->assertTrue(true);

			return;
		}

		$this->assertSame(
			$siteHost,
			$host,
			trim(
				sprintf('The redirect left the site: %s', $location) . "\n" . $message . "\n" . $response->summary()
			)
		);
	}

	/**
	 * Assert that the response body contains a string.
	 *
	 * @param   string    $needle    The string to look for.
	 * @param   Response  $response  The response.
	 * @param   string    $message   Extra context.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	protected function assertBodyContains(string $needle, Response $response, string $message = ''): void
	{
		// assertStringContainsString() would print the entire rendered page as the haystack, which
		// for a Joomla back-end view is tens of kilobytes of inline JSON and buries the message.
		$this->assertTrue(
			str_contains($response->body, $needle),
			trim(sprintf('Expected the response body to contain "%s".', $needle) . "\n" . $message . "\n" . $response->summary())
		);
	}

	/**
	 * Assert that the response body does NOT contain a string.
	 *
	 * @param   string    $needle    The string that must be absent.
	 * @param   Response  $response  The response.
	 * @param   string    $message   Extra context.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	protected function assertBodyNotContains(string $needle, Response $response, string $message = ''): void
	{
		$this->assertFalse(
			str_contains($response->body, $needle),
			trim(sprintf('Expected the response body NOT to contain "%s".', $needle) . "\n" . $message . "\n" . $response->summary())
		);
	}

	/**
	 * Does this body look like Joomla saying no?
	 *
	 * Primarily this looks at the SEVERITY of the rendered system message, not at its wording.
	 * Joomla enqueues a refusal as 'warning' or 'error' and a success as 'info' or 'success', and
	 * the template renders that severity into the markup — `<div class="alert alert-warning">` in
	 * Cassiopeia, `<joomla-alert type="warning">` elsewhere. Keying on that survives both a language
	 * change and a reworded language string, neither of which should break a security test.
	 *
	 * The phrases below are a backstop for refusals rendered as a full error page rather than a
	 * message, and each was copied from the shipped en-GB .ini rather than written from memory —
	 * JINVALID_TOKEN_NOTICE really is "The security token did not match…", not the "invalid token"
	 * one might reasonably expect.
	 *
	 * @param   string  $body  The response body.
	 *
	 * @return  bool
	 * @since   7.5.0
	 */
	private function bodyLooksLikeRefusal(string $body): bool
	{
		// Message severity, in either of the two shapes Joomla renders.
		if (preg_match('/alert-(warning|danger|error)\b/i', $body))
		{
			return true;
		}

		if (preg_match('/<joomla-alert\b[^>]*\btype\s*=\s*["\'](warning|danger|error)["\']/i', $body))
		{
			return true;
		}

		$needles = [
			// JINVALID_TOKEN_NOTICE
			'security token did not match',
			// JERROR_ALERTNOAUTHOR
			'not authorised to view this resource',
			'not authorized to view this resource',
			// Generic error pages
			'access denied',
			'not permitted',
			// COM_ARS_COMMON_ERR_NO_CATEGORIES, component/frontend/language/en-GB/com_ars.ini
			'you do not have access to any categories.',
			// COM_ARS_COMMON_ERR_NO_RELEASES, component/frontend/language/en-GB/com_ars.ini
			'you do not have access to any releases.',
		];

		foreach ($needles as $needle)
		{
			if (stripos($body, $needle) !== false)
			{
				return true;
			}
		}

		return false;
	}
}

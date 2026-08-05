<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Engine;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\SiteProbe;
use RuntimeException;

/**
 * Establishes real Joomla sessions for a {@see Surfer}.
 *
 * Ported from Admin Tools' Integration\Engine\Joomla, extended with a front-end login: ARS is
 * predominantly a front-end component, and most of the authorisation findings this suite regresses
 * are front-end ones.
 *
 * "Real" is the operative word. These log in by POSTing the actual login form with a token obtained
 * from the actual rendered page, so the resulting session is indistinguishable from a browser's.
 * Anything short of that — forging a session row, minting an API token — would not exercise the
 * code path the findings live in.
 *
 * @since 7.5.0
 */
class JoomlaSession
{
	/**
	 * Log a user into the site's front-end.
	 *
	 * @param   Surfer  $surfer    The surfer whose cookie jar receives the session.
	 * @param   string  $username  The username.
	 * @param   string  $password  The password.
	 *
	 * @return  void
	 * @throws  RuntimeException  When the login does not take effect.
	 * @since   7.5.0
	 */
	public function loginFrontend(Surfer $surfer, string $username, string $password): void
	{
		$loginUrl = 'index.php?option=com_users&view=login';
		$token    = $surfer->fetchToken($loginUrl);

		$response = $surfer->post(
			'index.php',
			[
				'option'   => 'com_users',
				'task'     => 'user.login',
				'username' => $username,
				'password' => $password,
				'return'   => base64_encode('index.php'),
				$token     => 1,
			]
		);

		$this->assertLoggedInFrontend($surfer, $username, $response, 'front-end');
	}

	/**
	 * Log a user into the site's back-end.
	 *
	 * @param   Surfer  $surfer    The surfer whose cookie jar receives the session.
	 * @param   string  $username  The username.
	 * @param   string  $password  The password.
	 *
	 * @return  void
	 * @throws  RuntimeException  When the login does not take effect.
	 * @since   7.5.0
	 */
	public function loginBackend(Surfer $surfer, string $username, string $password): void
	{
		$loginUrl = 'administrator/index.php';
		$token    = $surfer->fetchToken($loginUrl);

		$response = $surfer->post(
			$loginUrl,
			[
				'option'   => 'com_login',
				'task'     => 'login',
				'username' => $username,
				// The back-end login form calls this field `passwd`, not `password`.
				'passwd'   => $password,
				'lang'     => '',
				'return'   => base64_encode('index.php'),
				$token     => 1,
			]
		);

		if (!$this->isLoggedInBackend($surfer))
		{
			throw new RuntimeException(
				sprintf("Could not log in as '%s' on the back-end.\n%s", $username, $response->summary())
			);
		}
	}

	/**
	 * Is this surfer logged into the back-end?
	 *
	 * Checked separately from the front-end, and NOT with the identity probe: the probe boots the
	 * site application, which reads `session.web.site`. A back-end login writes
	 * `session.web.administrator` — a different session under a different cookie — so the probe
	 * would report a perfectly good administrator session as a guest.
	 *
	 * Instead we ask the back-end itself: if we are not authenticated it serves the login form, and
	 * that form's password field is the one unambiguous marker of it.
	 *
	 * @param   Surfer  $surfer  The surfer.
	 *
	 * @return  bool
	 * @since   7.5.0
	 */
	public function isLoggedInBackend(Surfer $surfer): bool
	{
		$wasFollowing            = $surfer->followRedirects;
		$surfer->followRedirects = true;

		try
		{
			$response = $surfer->get('administrator/index.php');

			return $response->code === 200 && stripos($response->body, 'name="passwd"') === false;
		}
		finally
		{
			$surfer->followRedirects = $wasFollowing;
		}
	}

	/**
	 * Log the surfer out of the front-end.
	 *
	 * @param   Surfer  $surfer  The surfer.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function logoutFrontend(Surfer $surfer): void
	{
		$response = $surfer->get('index.php', ['option' => 'com_users', 'view' => 'login']);
		$token    = $surfer->getFormToken($response->body);

		if ($token === null)
		{
			// Already logged out: the login view renders a login form, so no token means no session.
			$surfer->breakCookieJar();

			return;
		}

		$surfer->post(
			'index.php',
			[
				'option' => 'com_users',
				'task'   => 'user.logout',
				'return' => base64_encode('index.php'),
				$token   => 1,
			]
		);
	}

	/**
	 * Confirm that a login attempt actually produced an authenticated session.
	 *
	 * A failed Joomla login redirects back to the login form with an error message rather than
	 * returning an error status, so the HTTP code alone proves nothing. We verify by asking the site
	 * who it thinks we are.
	 *
	 * @param   Surfer    $surfer    The surfer.
	 * @param   string    $username  The username we expected to become.
	 * @param   Response  $response  The login response, for the failure message.
	 * @param   string    $where     'front-end' or 'back-end', for the failure message.
	 *
	 * @return  void
	 * @throws  RuntimeException
	 * @since   7.5.0
	 */
	private function assertLoggedInFrontend(Surfer $surfer, string $username, Response $response, string $where): void
	{
		if ($this->getCurrentUsername($surfer) === $username)
		{
			return;
		}

		throw new RuntimeException(
			sprintf(
				"Could not log in as '%s' on the %s.\n%s",
				$username,
				$where,
				$response->summary()
			)
		);
	}

	/**
	 * Ask the site which user the surfer's session belongs to.
	 *
	 * Answered by the identity probe the provisioner deploys into the web root, which boots the real
	 * site application and reports the session's identity. Parsing a rendered profile page instead
	 * would tie this to a template's markup and break between Joomla versions.
	 *
	 * @param   Surfer  $surfer  The surfer.
	 *
	 * @return  string|null  The username, or null when the session is a guest.
	 * @since   7.5.0
	 */
	public function getCurrentUsername(Surfer $surfer): ?string
	{
		$identity = $this->probeIdentity($surfer);

		if ($identity === null || !empty($identity['guest']))
		{
			return null;
		}

		return $identity['username'] ?: null;
	}

	/**
	 * Ask the identity probe for the full picture of who the session is.
	 *
	 * @param   Surfer  $surfer   The surfer.
	 * @param   array   $assets   Optional asset names to evaluate authorisation against.
	 * @param   array   $actions  Optional ACL actions to evaluate.
	 *
	 * @return  array|null  ['id', 'username', 'name', 'guest', 'groups', 'viewLevels', 'authorise'],
	 *                      or null when the probe is unavailable or answered unexpectedly.
	 * @since   7.5.0
	 */
	public function probeIdentity(Surfer $surfer, array $assets = [], array $actions = []): ?array
	{
		$wasFollowing            = $surfer->followRedirects;
		$surfer->followRedirects = true;

		try
		{
			$params = ['secret' => Configuration::getInstance()->getProbeSecret()];

			if ($assets !== [])
			{
				$params['assets'] = implode(',', $assets);
			}

			if ($actions !== [])
			{
				$params['actions'] = implode(',', $actions);
			}

			$response = $surfer->get(SiteProbe::ENDPOINT, $params);

			if ($response->code !== 200)
			{
				return null;
			}

			$data = $response->json();

			return is_array($data) ? $data : null;
		}
		finally
		{
			$surfer->followRedirects = $wasFollowing;
		}
	}
}

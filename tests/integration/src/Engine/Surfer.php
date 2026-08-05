<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Engine;

defined('_JEXEC') or die;

use CURLFile;
use RuntimeException;

/**
 * A web surfer: a cURL client with a persistent cookie jar.
 *
 * One Surfer is one browser session. The cookie jar is what makes CSRF and session-dependent
 * authorisation testable at all — without it there is no session, so there is no form token, and
 * "the request was refused" would be indistinguishable from "the request was never authenticated".
 *
 * Ported from Admin Tools' Integration\Engine\Surfer and extended per plan §5.2:
 *
 *   - {@see getFormToken()} pulls the anti-CSRF token out of *any* rendered form or GET link, not
 *     just the login page;
 *   - a token can be omitted or corrupted on purpose, which is how rejection is asserted;
 *   - redirects are captured rather than followed by default, so the Location header can be
 *     inspected — required for the open-redirect regression, where the bug *is* the redirect target.
 *
 * @since 7.5.0
 */
class Surfer
{
	/**
	 * A Joomla anti-CSRF token is a 32-character hexadecimal string.
	 *
	 * @since 7.5.0
	 */
	private const TOKEN_PATTERN = '[0-9a-f]{32}';

	/**
	 * Path to this surfer's cookie jar.
	 *
	 * @var   string
	 * @since 7.5.0
	 */
	protected string $cookieJar;

	/**
	 * Base URL of the site under test, without a trailing slash.
	 *
	 * @var   string
	 * @since 7.5.0
	 */
	protected string $baseUrl;

	/**
	 * User agent string.
	 *
	 * @var   string
	 * @since 7.5.0
	 */
	public string $uaString = 'Mozilla/5.0 (ARS end-to-end test harness)';

	/**
	 * Follow redirects instead of returning them?
	 *
	 * @var   bool
	 * @since 7.5.0
	 */
	public bool $followRedirects = false;

	/**
	 * Extra headers sent with every request made by this surfer.
	 *
	 * @var   array<string, string>
	 * @since 7.5.0
	 */
	public array $defaultHeaders = [];

	/**
	 * The last response this surfer received.
	 *
	 * @var   Response|null
	 * @since 7.5.0
	 */
	public ?Response $lastResponse = null;

	/**
	 * Constructor.
	 *
	 * @param   string  $baseUrl  Base URL of the site under test.
	 *
	 * @since   7.5.0
	 */
	public function __construct(string $baseUrl)
	{
		$this->baseUrl = rtrim($baseUrl, '/');
		$jar           = tempnam(sys_get_temp_dir(), 'ars-e2e-cookies-');

		if ($jar === false)
		{
			throw new RuntimeException('Could not create a cookie jar.');
		}

		$this->cookieJar = $jar;
	}

	/**
	 * Destructor: remove the cookie jar.
	 *
	 * @since 7.5.0
	 */
	public function __destruct()
	{
		if ($this->cookieJar !== '' && is_file($this->cookieJar))
		{
			@unlink($this->cookieJar);
		}
	}

	/**
	 * Throw the cookies away, i.e. become a brand new, logged-out browser.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function breakCookieJar(): void
	{
		@unlink($this->cookieJar);

		$jar = tempnam(sys_get_temp_dir(), 'ars-e2e-cookies-');

		if ($jar === false)
		{
			throw new RuntimeException('Could not create a cookie jar.');
		}

		$this->cookieJar = $jar;
	}

	/**
	 * The base URL of the site this surfer talks to.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function getBaseUrl(): string
	{
		return $this->baseUrl;
	}

	/**
	 * Resolve a possibly-relative URL against the base URL.
	 *
	 * @param   string  $url  An absolute URL, or one relative to the site root.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function absoluteUrl(string $url): string
	{
		if (preg_match('#^https?://#i', $url))
		{
			return $url;
		}

		return $this->baseUrl . '/' . ltrim($url, '/');
	}

	/**
	 * Perform a GET request.
	 *
	 * @param   string  $url      Absolute URL, or one relative to the site root.
	 * @param   array   $params   Query string parameters.
	 * @param   array   $headers  Extra request headers.
	 *
	 * @return  Response
	 * @since   7.5.0
	 */
	public function get(string $url, array $params = [], array $headers = []): Response
	{
		$request = $this->makeRequest('GET', $url, $headers);

		foreach ($params as $key => $value)
		{
			$request->setParameter($key, $value);
		}

		return $this->lastResponse = $request->getResponse();
	}

	/**
	 * Perform a POST request with a form body.
	 *
	 * @param   string        $url      Absolute URL, or one relative to the site root.
	 * @param   array|string  $data     The form body. An array containing a CURLFile is sent as
	 *                                  multipart/form-data.
	 * @param   array         $headers  Extra request headers.
	 *
	 * @return  Response
	 * @since   7.5.0
	 */
	public function post(string $url, $data = [], array $headers = []): Response
	{
		$request       = $this->makeRequest('POST', $url, $headers);
		$request->data = $data;

		return $this->lastResponse = $request->getResponse();
	}

	/**
	 * Perform a request with an arbitrary verb.
	 *
	 * @param   string             $verb     The HTTP verb.
	 * @param   string             $url      Absolute URL, or one relative to the site root.
	 * @param   array|string|null  $data     The request body, if any.
	 * @param   array              $headers  Extra request headers.
	 *
	 * @return  Response
	 * @since   7.5.0
	 */
	public function request(string $verb, string $url, $data = null, array $headers = []): Response
	{
		$request       = $this->makeRequest($verb, $url, $headers);
		$request->data = $data;

		return $this->lastResponse = $request->getResponse();
	}

	/**
	 * Upload a file through a form POST.
	 *
	 * @param   string  $url        Absolute URL, or one relative to the site root.
	 * @param   array   $data       The other form fields.
	 * @param   string  $fieldName  The name of the file input.
	 * @param   string  $filePath   Path to the file on disk.
	 * @param   string  $mimeType   The MIME type to declare.
	 * @param   string  $fileName   The filename to declare. Defaults to the basename of $filePath.
	 *
	 * @return  Response
	 * @since   7.5.0
	 */
	public function upload(
		string $url,
		array $data,
		string $fieldName,
		string $filePath,
		string $mimeType = 'application/octet-stream',
		string $fileName = ''
	): Response
	{
		if (!is_file($filePath))
		{
			throw new RuntimeException(sprintf('Cannot upload "%s": no such file.', $filePath));
		}

		$data[$fieldName] = new CURLFile($filePath, $mimeType, $fileName ?: basename($filePath));

		return $this->post($url, $data);
	}

	/**
	 * Extract Joomla's anti-CSRF token from a rendered page.
	 *
	 * Looks in two places, because ARS uses both:
	 *   - a hidden form input, `<input type="hidden" name="<token>" value="1">`;
	 *   - a GET link, `…&<token>=1`, which is how several back-end "toolbar" actions work.
	 *
	 * @param   string  $html  The rendered HTML.
	 *
	 * @return  string|null  Null when no token is present.
	 * @since   7.5.0
	 */
	public function getFormToken(string $html): ?string
	{
		// Hidden input, attributes in any order.
		if (preg_match_all('/<input\b[^>]*>/i', $html, $inputs))
		{
			foreach ($inputs[0] as $input)
			{
				if (!preg_match('/\btype\s*=\s*["\']?hidden["\']?/i', $input))
				{
					continue;
				}

				if (!preg_match('/\bvalue\s*=\s*["\']?1["\']?/i', $input))
				{
					continue;
				}

				if (preg_match('/\bname\s*=\s*["\'](' . self::TOKEN_PATTERN . ')["\']/i', $input, $match))
				{
					return strtolower($match[1]);
				}
			}
		}

		// GET link: &<token>=1 or ?<token>=1, possibly HTML-escaped as &amp;.
		if (preg_match('/[?&](?:amp;)?(' . self::TOKEN_PATTERN . ')=1\b/i', $html, $match))
		{
			return strtolower($match[1]);
		}

		return null;
	}

	/**
	 * Fetch a page and extract the anti-CSRF token from it.
	 *
	 * @param   string  $url      Absolute URL, or one relative to the site root.
	 * @param   array   $params   Query string parameters.
	 * @param   array   $headers  Extra request headers.
	 *
	 * @return  string  The token.
	 * @throws  RuntimeException  When the page contains no token, which is nearly always a broken
	 *                            fixture rather than a finding, and must not be silently treated as
	 *                            "no token needed".
	 * @since   7.5.0
	 */
	public function fetchToken(string $url, array $params = [], array $headers = []): string
	{
		$response = $this->get($url, $params, $headers);
		$token    = $this->getFormToken($response->body);

		if ($token === null)
		{
			throw new RuntimeException(
				sprintf("No anti-CSRF token found on the page.\n%s", $response->summary())
			);
		}

		return $token;
	}

	/**
	 * Produce a syntactically valid but wrong anti-CSRF token.
	 *
	 * Used to prove a request is refused because the token is *invalid*, not merely because it is
	 * missing or malformed — those are different code paths in Joomla.
	 *
	 * @param   string  $realToken  A real token to derive the corrupted one from, if available.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function corruptToken(string $realToken = ''): string
	{
		if ($realToken === '' || !preg_match('/^' . self::TOKEN_PATTERN . '$/', $realToken))
		{
			return str_repeat('0', 32);
		}

		// Flip the first character to something else in the hex alphabet, keeping the shape valid.
		$first = $realToken[0] === 'a' ? 'b' : 'a';

		return $first . substr($realToken, 1);
	}

	/**
	 * Build a request, applying this surfer's defaults.
	 *
	 * @param   string  $verb     The HTTP verb.
	 * @param   string  $url      Absolute URL, or one relative to the site root.
	 * @param   array   $headers  Extra request headers.
	 *
	 * @return  Request
	 * @since   7.5.0
	 */
	protected function makeRequest(string $verb, string $url, array $headers = []): Request
	{
		$request                 = new Request($verb, $this->cookieJar, $this->absoluteUrl($url));
		$request->uaString       = $this->uaString;
		$request->followLocation = $this->followRedirects;

		foreach (array_merge($this->defaultHeaders, $headers) as $name => $value)
		{
			$request->setHeader($name, $value);
		}

		return $request;
	}
}

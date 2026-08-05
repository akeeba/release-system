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
 * A single cURL request against the site under test.
 *
 * Ported from Admin Tools' Integration\Engine\Request, then reworked:
 *   - headers are collected as a name => values map instead of being flattened;
 *   - redirect following is off by default, because half of what we assert is *where* a refusal
 *     sends you, and cURL following the redirect destroys that evidence;
 *   - the status code is always captured, including on 4xx/5xx (cURL's return value alone is not
 *     enough to tell "server said no" from "connection failed").
 *
 * @since 7.5.0
 */
final class Request
{
	/**
	 * HTTP verb.
	 *
	 * @var   string
	 * @since 7.5.0
	 */
	private string $verb;

	/**
	 * Target URL.
	 *
	 * @var   string
	 * @since 7.5.0
	 */
	private string $url;

	/**
	 * Path to the shared cookie jar file.
	 *
	 * @var   string
	 * @since 7.5.0
	 */
	private string $cookieJar;

	/**
	 * Query string parameters appended to the URL.
	 *
	 * @var   array<string, string|int|null>
	 * @since 7.5.0
	 */
	private array $parameters = [];

	/**
	 * Request headers, name => value.
	 *
	 * @var   array<string, string>
	 * @since 7.5.0
	 */
	private array $headers = [];

	/**
	 * POST body: an array (url-encoded or multipart when it contains a CURLFile) or a raw string.
	 *
	 * @var   array|string|null
	 * @since 7.5.0
	 */
	public $data = null;

	/**
	 * User agent string.
	 *
	 * @var   string
	 * @since 7.5.0
	 */
	public string $uaString = 'Mozilla/5.0 (ARS end-to-end test harness)';

	/**
	 * Follow 3xx responses?
	 *
	 * Off by default: see the class docblock.
	 *
	 * @var   bool
	 * @since 7.5.0
	 */
	public bool $followLocation = false;

	/**
	 * Maximum number of redirects to follow when $followLocation is true.
	 *
	 * @var   int
	 * @since 7.5.0
	 */
	public int $maxRedirects = 10;

	/**
	 * Request timeout, in seconds.
	 *
	 * @var   int
	 * @since 7.5.0
	 */
	public int $timeout = 60;

	/**
	 * Constructor.
	 *
	 * @param   string  $verb       The HTTP verb.
	 * @param   string  $cookieJar  Path to the cookie jar file.
	 * @param   string  $url        The URL to request.
	 *
	 * @since   7.5.0
	 */
	public function __construct(string $verb, string $cookieJar, string $url = '')
	{
		$this->verb      = strtoupper($verb);
		$this->cookieJar = $cookieJar;
		$this->url       = $url;
	}

	/**
	 * Set a query string parameter.
	 *
	 * @param   string           $key    The parameter name.
	 * @param   string|int|null  $value  The parameter value.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function setParameter(string $key, $value): void
	{
		$this->parameters[$key] = $value;
	}

	/**
	 * Set a request header.
	 *
	 * @param   string  $key    The header name.
	 * @param   string  $value  The header value.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function setHeader(string $key, string $value): void
	{
		$this->headers[$key] = $value;
	}

	/**
	 * Execute the request.
	 *
	 * @return  Response
	 * @since   7.5.0
	 */
	public function getResponse(): Response
	{
		$response               = new Response();
		$response->requestedUrl = $this->buildUrl();

		$curl = curl_init();

		if ($curl === false)
		{
			throw new RuntimeException('Could not initialise cURL.');
		}

		curl_setopt_array(
			$curl,
			[
				CURLOPT_URL            => $response->requestedUrl,
				CURLOPT_USERAGENT      => $this->uaString,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER         => false,
				CURLOPT_COOKIEJAR      => $this->cookieJar,
				CURLOPT_COOKIEFILE     => $this->cookieJar,
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_TIMEOUT        => $this->timeout,
				CURLOPT_FOLLOWLOCATION => $this->followLocation,
				CURLOPT_MAXREDIRS      => $this->maxRedirects,
				// The site under test is plain HTTP on localhost; there is nothing to verify.
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_SSL_VERIFYPEER => false,
			]
		);

		// Collect response headers. Reset on each header block so that, when we DO follow
		// redirects, we end up with the final response's headers rather than a merge of all of them.
		curl_setopt(
			$curl,
			CURLOPT_HEADERFUNCTION,
			function ($curlHandle, string $line) use ($response): int {
				$length = strlen($line);
				$header = trim($line);

				if ($header === '')
				{
					return $length;
				}

				if (stripos($header, 'HTTP/') === 0)
				{
					$response->headers = [];
					$parts             = explode(' ', $header, 3);
					$response->code    = (int) ($parts[1] ?? 0);

					return $length;
				}

				if (strpos($header, ':') === false)
				{
					return $length;
				}

				[$name, $value] = explode(':', $header, 2);

				$response->headers[strtolower(trim($name))][] = trim($value);

				return $length;
			}
		);

		if ($this->headers !== [])
		{
			$headerLines = [];

			foreach ($this->headers as $name => $value)
			{
				$headerLines[] = $name . ': ' . $value;
			}

			curl_setopt($curl, CURLOPT_HTTPHEADER, $headerLines);
		}

		switch ($this->verb)
		{
			case 'GET':
				break;

			case 'HEAD':
				curl_setopt($curl, CURLOPT_NOBODY, true);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
				break;

			default:
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);

				if ($this->data !== null)
				{
					// An array containing a CURLFile must be handed to cURL as an array, so it
					// encodes multipart/form-data. Anything else is url-encoded explicitly, because
					// cURL's own array handling would otherwise switch to multipart unexpectedly.
					curl_setopt($curl, CURLOPT_POSTFIELDS, $this->encodeBody($this->data));
				}
				break;
		}

		$body = curl_exec($curl);

		if ($body === false)
		{
			$response->error = [
				'code'    => curl_errno($curl),
				'message' => curl_error($curl),
			];
		}
		else
		{
			$response->body = (string) $body;
		}

		$response->effectiveUrl  = (string) curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
		$response->redirectCount = (int) curl_getinfo($curl, CURLINFO_REDIRECT_COUNT);

		if ($response->code === 0)
		{
			$response->code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
		}

		// curl_close() is a no-op since PHP 8.0 and deprecated in 8.5.
		if (version_compare(PHP_VERSION, '8.5.0', 'lt'))
		{
			@curl_close($curl);
		}

		return $response;
	}

	/**
	 * Append the query string parameters to the URL.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	private function buildUrl(): string
	{
		if ($this->parameters === [])
		{
			return $this->url;
		}

		$separator = strpos($this->url, '?') === false ? '?' : '&';

		return $this->url . $separator . http_build_query($this->parameters);
	}

	/**
	 * Encode a request body for cURL.
	 *
	 * @param   array|string  $data  The body.
	 *
	 * @return  array|string
	 * @since   7.5.0
	 */
	private function encodeBody($data)
	{
		if (!is_array($data))
		{
			return $data;
		}

		foreach ($data as $value)
		{
			if ($value instanceof CURLFile)
			{
				return $data;
			}
		}

		return http_build_query($data);
	}
}

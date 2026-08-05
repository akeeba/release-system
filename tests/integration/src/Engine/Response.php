<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Engine;

defined('_JEXEC') or die;

/**
 * An HTTP response, as observed by the Surfer.
 *
 * Deliberately keeps the status code and the raw headers, because most of what this suite asserts
 * is *refusal*: a 403, or a redirect whose Location must not point where an attacker asked.
 *
 * @since 7.5.0
 */
class Response
{
	/**
	 * The HTTP status code of the final response.
	 *
	 * @var   int
	 * @since 7.5.0
	 */
	public int $code = 0;

	/**
	 * The response body.
	 *
	 * @var   string
	 * @since 7.5.0
	 */
	public string $body = '';

	/**
	 * Response headers, lower-cased name => list of values.
	 *
	 * A list, not a scalar: Set-Cookie legitimately repeats, and collapsing it would hide exactly
	 * the session behaviour this harness exists to observe.
	 *
	 * @var   array<string, string[]>
	 * @since 7.5.0
	 */
	public array $headers = [];

	/**
	 * The URL actually requested (after any redirects cURL followed).
	 *
	 * @var   string
	 * @since 7.5.0
	 */
	public string $effectiveUrl = '';

	/**
	 * The URL originally requested.
	 *
	 * @var   string
	 * @since 7.5.0
	 */
	public string $requestedUrl = '';

	/**
	 * Number of redirects cURL followed.
	 *
	 * @var   int
	 * @since 7.5.0
	 */
	public int $redirectCount = 0;

	/**
	 * A transport-level error, if any: ['code' => int, 'message' => string].
	 *
	 * A non-null value here means the request never completed — distinct from a completed request
	 * that returned an error status.
	 *
	 * @var   array|null
	 * @since 7.5.0
	 */
	public ?array $error = null;

	/**
	 * Get the first value of a response header.
	 *
	 * @param   string  $name  Header name, case-insensitive.
	 *
	 * @return  string|null  Null when the header is absent.
	 * @since   7.5.0
	 */
	public function getHeader(string $name): ?string
	{
		$values = $this->headers[strtolower($name)] ?? [];

		return $values[0] ?? null;
	}

	/**
	 * Get every value of a response header.
	 *
	 * @param   string  $name  Header name, case-insensitive.
	 *
	 * @return  string[]
	 * @since   7.5.0
	 */
	public function getHeaders(string $name): array
	{
		return $this->headers[strtolower($name)] ?? [];
	}

	/**
	 * The Location header, if this response is a redirect.
	 *
	 * @return  string|null
	 * @since   7.5.0
	 */
	public function getLocation(): ?string
	{
		return $this->getHeader('location');
	}

	/**
	 * Is this a redirect response (3xx with a Location)?
	 *
	 * @return  bool
	 * @since   7.5.0
	 */
	public function isRedirect(): bool
	{
		return $this->code >= 300 && $this->code < 400 && $this->getLocation() !== null;
	}

	/**
	 * Decode the body as JSON.
	 *
	 * @param   bool  $assoc  Return associative arrays instead of objects?
	 *
	 * @return  mixed  Null when the body is not valid JSON.
	 * @since   7.5.0
	 */
	public function json(bool $assoc = true)
	{
		return json_decode($this->body, $assoc);
	}

	/**
	 * A compact, human-readable summary, for use in assertion failure messages.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function summary(): string
	{
		$out = sprintf('HTTP %d for %s', $this->code, $this->requestedUrl);

		if ($this->error !== null)
		{
			$out .= sprintf(' [transport error %d: %s]', $this->error['code'], $this->error['message']);
		}

		if ($location = $this->getLocation())
		{
			$out .= sprintf(' → %s', $location);
		}

		$body = trim(preg_replace('/\s+/', ' ', strip_tags($this->body)) ?? '');

		if ($body !== '')
		{
			$out .= "\nBody excerpt: " . mb_substr($body, 0, 400);
		}

		return $out;
	}
}

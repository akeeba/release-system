<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Engine;

defined('_JEXEC') or die;

use RuntimeException;

/**
 * Reads the mail the site actually sent, out of the Mailpit sink.
 *
 * Joomla is configured to deliver over real SMTP to smtp://mailpit:1025, so everything from the
 * mailer configuration through PHPMailer, the SMTP dialogue, MIME encoding, multiple recipients,
 * Cc/Bcc, headers and attachments is exercised for real. Stubbing Mail::Send() would have proved
 * none of it, and would have meant maintaining a fork of a core class across a multi-version matrix.
 *
 * Mailpit is a sink: it accepts messages and never relays them.
 *
 * The endpoints below were verified against the pinned image (Mailpit v1.21.8), not assumed:
 *   GET    /api/v1/info
 *   GET    /api/v1/messages?limit=…&start=…
 *   GET    /api/v1/message/{ID}
 *   GET    /api/v1/search?query=…
 *   DELETE /api/v1/messages
 *
 * Note that Mailpit's JSON uses PascalCase keys (ID, From, To, Subject, Text, HTML, Attachments).
 *
 * @since 7.5.0
 */
class Mailpit
{
	/**
	 * Base URL of the Mailpit REST API.
	 *
	 * @var   string
	 * @since 7.5.0
	 */
	private string $baseUrl;

	/**
	 * Constructor.
	 *
	 * @param   string|null  $baseUrl  Base URL. Defaults to the configured one.
	 *
	 * @since   7.5.0
	 */
	public function __construct(?string $baseUrl = null)
	{
		$this->baseUrl = rtrim($baseUrl ?? Configuration::getInstance()->getMailpitUrl(), '/');
	}

	/**
	 * Is Mailpit reachable?
	 *
	 * @return  bool
	 * @since   7.5.0
	 */
	public function isAvailable(): bool
	{
		try
		{
			$this->call('GET', '/api/v1/info');

			return true;
		}
		catch (RuntimeException $e)
		{
			return false;
		}
	}

	/**
	 * Delete every stored message.
	 *
	 * Call this at the start of a test that asserts on mail, so the assertion is about the mail this
	 * test caused and not something left over from the last one.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function clear(): void
	{
		$this->call('DELETE', '/api/v1/messages');
	}

	/**
	 * How many messages are stored.
	 *
	 * @return  int
	 * @since   7.5.0
	 */
	public function count(): int
	{
		$result = $this->call('GET', '/api/v1/messages?limit=1');

		return (int) ($result['messages_count'] ?? 0);
	}

	/**
	 * List stored messages, newest first.
	 *
	 * @param   int  $limit  Maximum number to return.
	 *
	 * @return  array[]  Message summaries.
	 * @since   7.5.0
	 */
	public function list(int $limit = 50): array
	{
		$result = $this->call('GET', '/api/v1/messages?limit=' . $limit);

		return $result['messages'] ?? [];
	}

	/**
	 * Fetch one message in full: headers, text part, HTML part, attachments.
	 *
	 * @param   string  $id  The Mailpit message ID.
	 *
	 * @return  array
	 * @since   7.5.0
	 */
	public function message(string $id): array
	{
		return $this->call('GET', '/api/v1/message/' . rawurlencode($id));
	}

	/**
	 * Search messages using Mailpit's query syntax, e.g. `to:alice@example.test`.
	 *
	 * @param   string  $query  The search query.
	 *
	 * @return  array[]  Message summaries.
	 * @since   7.5.0
	 */
	public function search(string $query): array
	{
		$result = $this->call('GET', '/api/v1/search?query=' . rawurlencode($query));

		return $result['messages'] ?? [];
	}

	/**
	 * Every message addressed to a given recipient, in To, Cc or Bcc.
	 *
	 * Mailpit stores all recipients of every message, which is why this can be honest about a
	 * component that routinely mails several people per release.
	 *
	 * @param   string  $address  The email address.
	 *
	 * @return  array[]  Message summaries.
	 * @since   7.5.0
	 */
	public function messagesTo(string $address): array
	{
		$address = strtolower($address);

		return array_values(
			array_filter(
				$this->list(200),
				function (array $message) use ($address): bool {
					foreach (['To', 'Cc', 'Bcc'] as $field)
					{
						foreach ($message[$field] ?? [] as $recipient)
						{
							if (strtolower($recipient['Address'] ?? '') === $address)
							{
								return true;
							}
						}
					}

					return false;
				}
			)
		);
	}

	/**
	 * Every recipient address of a message summary, across To, Cc and Bcc.
	 *
	 * @param   array  $message  A message summary or full message.
	 *
	 * @return  string[]  Lower-cased addresses.
	 * @since   7.5.0
	 */
	public static function recipientsOf(array $message): array
	{
		$addresses = [];

		foreach (['To', 'Cc', 'Bcc'] as $field)
		{
			foreach ($message[$field] ?? [] as $recipient)
			{
				if (!empty($recipient['Address']))
				{
					$addresses[] = strtolower($recipient['Address']);
				}
			}
		}

		return array_values(array_unique($addresses));
	}

	/**
	 * Wait until at least $count messages have arrived, or give up.
	 *
	 * SMTP delivery to Mailpit is synchronous with the request that triggered it, so in practice the
	 * mail is already there by the time the HTTP response comes back. This exists for the cases
	 * where it is not — and it fails loudly rather than letting a test assert against an empty inbox
	 * and call that a pass.
	 *
	 * @param   int    $count           How many messages to wait for.
	 * @param   float  $timeoutSeconds  How long to wait.
	 *
	 * @return  array[]  The message summaries.
	 * @throws  RuntimeException  When the messages do not arrive in time.
	 * @since   7.5.0
	 */
	public function waitForMessages(int $count = 1, float $timeoutSeconds = 10.0): array
	{
		$deadline = microtime(true) + $timeoutSeconds;

		do
		{
			$messages = $this->list(200);

			if (count($messages) >= $count)
			{
				return $messages;
			}

			usleep(200000);
		}
		while (microtime(true) < $deadline);

		throw new RuntimeException(
			sprintf(
				'Expected at least %d message(s) in Mailpit within %.1fs, but only %d arrived.',
				$count,
				$timeoutSeconds,
				count($messages)
			)
		);
	}

	/**
	 * Perform a REST call.
	 *
	 * @param   string  $method  The HTTP verb.
	 * @param   string  $path    The path, including any query string.
	 *
	 * @return  array  The decoded response, or an empty array for an empty body.
	 * @throws  RuntimeException  On a transport failure or a non-2xx status.
	 * @since   7.5.0
	 */
	private function call(string $method, string $path): array
	{
		$url  = $this->baseUrl . $path;
		$curl = curl_init();

		curl_setopt_array(
			$curl,
			[
				CURLOPT_URL            => $url,
				CURLOPT_CUSTOMREQUEST  => $method,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_TIMEOUT        => 20,
			]
		);

		$body  = curl_exec($curl);
		$errNo = curl_errno($curl);
		$error = curl_error($curl);
		$code  = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if (version_compare(PHP_VERSION, '8.5.0', 'lt'))
		{
			@curl_close($curl);
		}

		if ($body === false)
		{
			throw new RuntimeException(sprintf('Mailpit request to %s failed (cURL %d): %s', $url, $errNo, $error));
		}

		if ($code < 200 || $code >= 300)
		{
			throw new RuntimeException(sprintf('Mailpit request to %s returned HTTP %d: %s', $url, $code, $body));
		}

		if (trim((string) $body) === '')
		{
			return [];
		}

		$decoded = json_decode((string) $body, true);

		return is_array($decoded) ? $decoded : [];
	}
}

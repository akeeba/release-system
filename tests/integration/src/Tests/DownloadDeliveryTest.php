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
 * How the bytes actually come back: headers, integrity, HTTP Range support and link-item redirects.
 *
 * See `ItemModel::doDownload()`, `downloadFileItem()`, `downloadLinkItem()` and
 * `getContentDigestHeaderValue()`.
 *
 * @since 7.5.0
 */
#[Group('download')]
class DownloadDeliveryTest extends AbstractE2ETestCase
{
	/**
	 * The original `#__extensions` params for com_ars, captured by the one test that mutates them, so
	 * {@see tearDown()} can put them back.
	 *
	 * @var   string|null
	 * @since 7.5.0
	 */
	private ?string $originalComponentParams = null;

	/**
	 * Restore the component parameters if the permanent-redirect test changed them.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	protected function tearDown(): void
	{
		if ($this->originalComponentParams !== null)
		{
			$this->db()->query(
				"UPDATE `#__extensions` SET `params` = :params WHERE `element` = 'com_ars' AND `type` = 'component'",
				[':params' => $this->originalComponentParams]
			);

			$this->originalComponentParams = null;
		}

		parent::tearDown();
	}

	/**
	 * A guest downloading the public file gets the whole, intact file: the right disposition header,
	 * and a body whose sha256 matches the manifest exactly — proof the entire file arrived, not just
	 * that something did.
	 *
	 * Not asserted: `Content-Length`. Confirmed empirically (`curl -D -`, both HTTP/1.0 and HTTP/1.1)
	 * that the response never carries one — Apache switches to `Transfer-Encoding: chunked` instead.
	 * The original diagnosis blamed `downloadFileItem()`'s repeated `flush()` calls, but that does not
	 * hold up: a throwaway probe script placed in the site root that set `Content-Length` and echoed
	 * 100 KB in one go, with no `flush()` at all, was also served chunked, and so was a five-byte
	 * `header('Content-Length: 5'); echo 'HELLO';` script — while a *static* file from the same server
	 * came back with a correct `Content-Length`. `zlib.output_compression` is off and
	 * `output_buffering` is 0, ruling out compression too. The real cause is the test container's
	 * front end: this stack serves PHP through `mod_proxy_fcgi` to a separate PHP-FPM container, and
	 * every PHP response through that path is chunked regardless of what the script does. `ItemModel`
	 * sets the header correctly, before any output; the test environment's web server drops it
	 * unconditionally. That is an artefact of this Dockerised site, not an ARS defect — the
	 * body-length and sha256 checks below are the assertions that actually matter, and they do not
	 * depend on that header existing.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testGuestDownloadGetsTheWholeIntactFile(): void
	{
		$file     = static::$fixtures->file('publicFile');
		$response = $this->guest()->get($this->downloadUrl('publicFile'));

		$this->assertStatus(200, $response, 'The public file did not download.');
		$this->assertSame(
			'attachment; filename="' . basename($file['relative']) . '"',
			$response->getHeader('content-disposition'),
			'The Content-Disposition header does not name the file as an attachment.'
		);
		$this->assertSame(
			(int) $file['size'],
			strlen($response->body),
			'The downloaded body is not the size the manifest recorded.'
		);
		$this->assertSame(
			$file['sha256'],
			hash('sha256', $response->body),
			'The downloaded body does not hash to the sha256 the manifest recorded, so the file arrived corrupted.'
		);
	}

	/**
	 * The `content_digest` component parameter (pinned on by the fixtures) adds a `Content-Digest`
	 * header in RFC 9530 format, and its value is the base64 sha-512 of the file.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testResponseCarriesAMatchingContentDigestHeader(): void
	{
		$file     = static::$fixtures->file('publicFile');
		$response = $this->guest()->get($this->downloadUrl('publicFile'));

		$digest = $response->getHeader('content-digest');

		$this->assertNotNull($digest, 'No Content-Digest header was sent, though content_digest is pinned on.');
		$this->assertMatchesRegularExpression('/^sha-512=:[A-Za-z0-9+\/=]+:$/', $digest, 'The Content-Digest header is not in RFC 9530 sha-512 format.');

		$expectedDigest = 'sha-512=:' . base64_encode(hash_file('sha512', $this->onDiskPath($file), true)) . ':';

		$this->assertSame($expectedDigest, $digest, 'The Content-Digest value does not match the sha-512 of the actual file on disk.');
	}

	/**
	 * A `Range: bytes=0-1023` request gets a 206 with a correct `Content-Range` header, and the first
	 * 1024 bytes of the body are exactly that slice of the file.
	 *
	 * The exact body length is asserted separately, in
	 * {@see testByteRangeResponseBodyIsExactlyTheRequestedLength()}. That is the assertion that used
	 * to fail: `downloadFileItem()`'s chunked-read loop guarded with `if ($chunkSize < 0)`, which does
	 * not catch a `$chunkSize` of exactly 0 — the value it takes once the requested range has been
	 * fully read. `fread()` with a zero length throws a `ValueError` on PHP 8, so the already-flushed
	 * bytes were followed by a 300-byte fragment of Joomla's generic error page inside the SAME
	 * response, corrupting every ranged and resumable download. Fixed by widening that guard to
	 * `<= 0`; keep both tests, because the status line and the Content-Range header were correct
	 * throughout and would not have caught it.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testByteRangeRequestReturnsPartialContentWithACorrectContentRangeHeader(): void
	{
		$file     = static::$fixtures->file('publicFile');
		$response = $this->guest()->get($this->downloadUrl('publicFile'), [], ['Range' => 'bytes=0-1023']);

		$this->assertStatus(206, $response, 'A byte-range request did not get a 206 Partial Content response.');
		$this->assertSame(
			'bytes 0-1023/' . $file['size'],
			$response->getHeader('content-range'),
			'The Content-Range header does not describe the requested range correctly.'
		);
		$this->assertSame(
			substr((string) file_get_contents($this->onDiskPath($file)), 0, 1024),
			substr($response->body, 0, 1024),
			'The first 1024 bytes of the ranged response do not match the requested slice of the file on disk.'
		);
	}

	/**
	 * A Range response body is exactly the requested length, and exactly the right bytes.
	 *
	 * This is the regression test for the `fread($handle, 0)` `ValueError` described on
	 * {@see testByteRangeRequestReturnsPartialContentWithACorrectContentRangeHeader()}. It is kept
	 * separate and asserts the length *first*, because that is the only thing that was ever wrong: a
	 * corrupted response still carried the right status, the right Content-Range, and the right
	 * leading 1024 bytes. Anything that checked only those passed while the download was broken.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testByteRangeResponseBodyIsExactlyTheRequestedLength(): void
	{
		$file     = static::$fixtures->file('publicFile');
		$response = $this->guest()->get($this->downloadUrl('publicFile'), [], ['Range' => 'bytes=0-1023']);

		$this->assertSame(
			1024,
			strlen($response->body),
			'A byte-range response was not exactly the requested length. Anything longer means trailing '
			. 'bytes were appended to the body — historically a fragment of Joomla\'s error page, from an '
			. 'uncaught ValueError in the chunked-read loop.'
		);

		$this->assertSame(
			substr((string) file_get_contents($this->onDiskPath($file)), 0, 1024),
			$response->body,
			'The ranged response body is the right length but not the right bytes.'
		);
	}

	/**
	 * A range that crosses the model's 1 MiB chunk-read boundary still serves the correct bytes for
	 * that slice — proven against the file on disk, not against a hard-coded expectation.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testMidFileRangeMatchesTheOnDiskSlice(): void
	{
		$file     = static::$fixtures->file('publicFile');
		$response = $this->guest()->get($this->downloadUrl('publicFile'), [], ['Range' => 'bytes=1048576-1049599']);

		$this->assertStatus(206, $response, 'A mid-file byte-range request did not get a 206 Partial Content response.');
		$this->assertSame(
			'bytes 1048576-1049599/' . $file['size'],
			$response->getHeader('content-range'),
			'The Content-Range header does not describe the requested mid-file range correctly.'
		);

		$onDiskSlice = file_get_contents($this->onDiskPath($file), false, null, 1048576, 1024);

		// This offset starts exactly on the model's 1 MiB chunk boundary, which is what made it a
		// useful second witness for the fread($handle, 0) bug: the whole body is compared, not a
		// prefix of it, so trailing bytes beyond the requested range fail the assertion.
		$this->assertSame(
			$onDiskSlice,
			$response->body,
			'The served mid-file range does not match the same slice of the file on disk.'
		);
	}

	/**
	 * A malformed `Range: bytes=abc` header does not error out with a 5xx or 4xx. Recorded, not
	 * asserted as obviously correct: `downloadFileItem()`'s range parsing is lenient to the point of
	 * silently treating an unparseable range as "the whole file" (see the arithmetic below), which is a
	 * defensible fallback for a malformed request but is worth knowing about explicitly rather than
	 * discovering by accident.
	 *
	 * This path used to hit the same chunked-read bug as the byte-range tests, because the "whole file"
	 * fallback still sets `$isResumable = true` and so still ended on `fread($handle, 0)`. It is now
	 * asserted to deliver the file and nothing but the file.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testMalformedRangeHeaderFallsBackToTheWholeFile(): void
	{
		$file     = static::$fixtures->file('publicFile');
		$response = $this->guest()->get($this->downloadUrl('publicFile'), [], ['Range' => 'bytes=abc']);

		$this->assertNotContains(
			$response->code,
			[500, 502, 503, 504],
			'A malformed Range header caused a server error instead of a graceful fallback.'
		);
		$this->assertSame(200, $response->code, 'A malformed Range header did not fall back to serving the whole file with a 200.');
		$this->assertSame(
			(int) $file['size'],
			strlen($response->body),
			'The fallback response is not exactly filesize() bytes, so something was appended to the body.'
		);
		$this->assertSame(
			$file['sha256'],
			hash('sha256', $response->body),
			'The fallback response does not hash to the file on disk.'
		);
	}

	/**
	 * A link item, with the default `url_dl=temp` component parameter, redirects with a 303 rather than
	 * being fetched and proxied. The Location is asserted directly, without following it: the target
	 * (`http://web/e2e-link-target.txt`) only resolves inside the compose network.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testLinkItemRedirectsTemporarilyByDefault(): void
	{
		$response = $this->guest()->get($this->downloadUrl('publicLink'));

		$this->assertStatus(303, $response, 'A link item with the default url_dl=temp setting did not issue a 303.');
		$this->assertSame(
			'http://web/e2e-link-target.txt',
			$response->getLocation(),
			'The link item redirected somewhere other than its configured URL.'
		);
	}

	/**
	 * Flipping the `url_dl` component parameter to `permanent` changes the link-item redirect to a 301.
	 * This is the one mutation this class makes to the live site; the original params are restored in
	 * {@see tearDown()} regardless of how the test finishes.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testLinkItemRedirectsPermanentlyWhenConfigured(): void
	{
		$this->originalComponentParams = (string) $this->db()->value(
			"SELECT `params` FROM `#__extensions` WHERE `element` = 'com_ars' AND `type` = 'component'"
		);

		$params           = json_decode($this->originalComponentParams, true) ?: [];
		$params['url_dl'] = 'permanent';

		$this->db()->query(
			"UPDATE `#__extensions` SET `params` = :params WHERE `element` = 'com_ars' AND `type` = 'component'",
			[':params' => json_encode($params)]
		);

		$response = $this->guest()->get($this->downloadUrl('publicLink'));

		$this->assertStatus(301, $response, 'A link item did not issue a 301 once url_dl was set to permanent.');
		$this->assertSame(
			'http://web/e2e-link-target.txt',
			$response->getLocation(),
			'The link item redirected somewhere other than its configured URL.'
		);
	}

	/**
	 * Build the download URL for a fixture item.
	 *
	 * @param   string  $itemKey  An item name from the fixture manifest.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	private function downloadUrl(string $itemKey): string
	{
		return $this->siteUrl([
			'view'    => 'item',
			'task'    => 'download',
			'format'  => 'raw',
			'item_id' => static::$fixtures->itemId($itemKey),
		]);
	}

	/**
	 * Resolve a fixture file's manifest entry to its actual path on the host.
	 *
	 * The manifest's `absolute` path is the path INSIDE the container (`/var/www/html/...`), which
	 * does not exist on the host running PHPUnit; the host-visible path is the site root plus the
	 * manifest's `relative` path.
	 *
	 * @param   array  $file  A `file()` fixture entry.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	private function onDiskPath(array $file): string
	{
		return rtrim(static::$config->getSiteRoot(), '/') . '/' . ltrim((string) $file['relative'], '/');
	}
}

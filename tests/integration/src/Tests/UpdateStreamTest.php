<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Tests;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\AbstractE2ETestCase;
use DOMDocument;
use PHPUnit\Framework\Attributes\Group;
use SimpleXMLElement;

/**
 * The Joomla updater feed. Headline coverage is the Joomla 6.2 security-flag feature (commit
 * `37ad5eed`): the `<security>` element must be emitted, with the right severity, for the one release
 * that carries one, and omitted ENTIRELY — not emitted as `<security>0</security>` — for every other
 * release. `<security>0</security>` and no element at all look identical to a substring search, which
 * is exactly why this class parses the XML with `DOMDocument` and counts elements instead.
 *
 * @since 7.5.0
 */
#[Group('update')]
class UpdateStreamTest extends AbstractE2ETestCase
{
	/**
	 * The `main` stream's XML carries `<security>3</security>` for the one release that has a
	 * severity, and no `<security>` element at all for any other release in the same stream.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testSecurityElementIsEmittedOnlyForTheSeverityCarryingRelease(): void
	{
		$response = $this->guest()->get($this->streamUrl('main'));

		$this->assertStatus(200, $response, 'The main update stream did not render.');

		$dom = new DOMDocument();
		$this->assertTrue(
			$dom->loadXML($response->body),
			"The main update stream's response body is not well-formed XML."
		);

		$updates = $dom->getElementsByTagName('update');
		$this->assertGreaterThan(0, $updates->length, 'The main update stream carries no <update> elements at all.');

		$withSecurity  = 0;
		$securityValue = null;

		foreach ($updates as $update)
		{
			/** @var \DOMElement $update */
			$securityNodes = $update->getElementsByTagName('security');

			if ($securityNodes->length === 0)
			{
				continue;
			}

			$this->assertSame(
				1,
				$securityNodes->length,
				'An <update> element carries more than one <security> element.'
			);

			$withSecurity++;
			$securityValue = $securityNodes->item(0)->textContent;
		}

		$this->assertSame(
			1,
			$withSecurity,
			'The main stream should carry exactly one <update> with a <security> element (the publicSecurity '
			. 'release), matching the single release the fixtures give a non-zero severity.'
		);
		$this->assertSame('3', $securityValue, 'The <security> element does not carry the severity 3 the publicSecurity release has.');
	}

	/**
	 * The JSON output carries the equivalent `security` value.
	 *
	 * This one used to be skipped: `task=json` returned HTTP 500 for three compounding reasons, all of
	 * which are fixed and each of which is pinned by one of the tests below.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testJsonOutputCarriesTheEquivalentSecurityValue(): void
	{
		$response = $this->guest()->get($this->jsonStreamUrl('main'));

		$this->assertStatus(200, $response, 'The JSON update stream did not render.');
		$this->assertStringStartsWith(
			'application/json',
			$response->getHeader('content-type') ?? '',
			'The JSON update stream is not served as application/json.'
		);

		$items = $response->json();
		$this->assertIsArray($items, 'The JSON update stream did not return a JSON array.');

		$withSecurity = array_filter($items, fn(array $item) => array_key_exists('security', $item));
		$this->assertCount(1, $withSecurity, 'The JSON update stream should carry exactly one item with a security field.');
		$this->assertSame(3, (int) array_values($withSecurity)[0]['security'], 'The JSON security value is not 3.');
	}

	/**
	 * `task=stream&format=json` returns JSON, not XML.
	 *
	 * The `json` task is the `stream` task rendered as JSON — `UpdateController::json()` literally calls
	 * `stream()`. Asking for the stream in JSON must therefore give you JSON. It used to give you XML:
	 * the `default:` arm of `onBeforeExecute()`'s task/format reconciliation forced `format` back to
	 * `xml` for every task it did not recognise as JSON-capable, silently discarding what the request
	 * asked for.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testStreamTaskHonoursTheJsonFormat(): void
	{
		$viaStreamTask = $this->guest()->get($this->siteUrl([
			'view'   => 'update',
			'task'   => 'stream',
			'format' => 'json',
			'id'     => static::$fixtures->updateStreamId('main'),
		]));

		$this->assertStatus(200, $viaStreamTask, 'task=stream&format=json did not render.');
		$this->assertIsArray(
			$viaStreamTask->json(),
			'task=stream&format=json did not return JSON. It most likely returned the XML stream instead: '
			. substr($viaStreamTask->body, 0, 120)
		);

		$viaJsonTask = $this->guest()->get($this->jsonStreamUrl('main'));

		$this->assertSame(
			$viaJsonTask->json(),
			$viaStreamTask->json(),
			'task=stream&format=json and task=json do not produce the same update stream.'
		);
	}

	/**
	 * Every update-stream task renders identically whether or not `format` is in the URL.
	 *
	 * `UpdateController::onBeforeExecute()` replaces the application's document object when the URL does
	 * not already carry the right `format`. `SiteApplication::dispatch()` has by then taken a reference
	 * to the OLD document, and writes the component's output into it — into the STATIC, shared
	 * `Document::$_buffer`, in HtmlDocument's nested `[$type][$name][$title]` shape rather than the plain
	 * string an Xml/Json/Raw document hands straight back from `render()`.
	 *
	 * The result was the literal string "Array" (plus an "Array to string conversion" warning) appended
	 * to every response, and — for JSON, where core's `JsonView::display()` had already put a *string*
	 * where HtmlDocument expected an array — an outright HTTP 500 with the update stream replaced by an
	 * unrenderable error page.
	 *
	 * The bodies must be byte-identical, not merely both 200. Trailing garbage is precisely what this
	 * catches.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testTasksRenderIdenticallyWithAndWithoutAnExplicitFormat(): void
	{
		$streamId = static::$fixtures->updateStreamId('main');
		$cases    = [
			'all'    => [['view' => 'update', 'task' => 'all'], 'xml'],
			'stream' => [['view' => 'update', 'task' => 'stream', 'id' => $streamId], 'xml'],
			'ini'    => [['view' => 'update', 'task' => 'ini', 'id' => $streamId], 'ini'],
			'json'   => [['view' => 'update', 'task' => 'json', 'id' => $streamId], 'json'],
		];

		foreach ($cases as $task => [$query, $format])
		{
			$without = $this->guest()->get($this->siteUrl($query));
			$with    = $this->guest()->get($this->siteUrl(array_merge($query, ['format' => $format])));

			$this->assertStatus(200, $without, sprintf('task=%s without an explicit format did not render.', $task));
			$this->assertStatus(200, $with, sprintf('task=%s with format=%s did not render.', $task, $format));

			$this->assertSame(
				$with->body,
				$without->body,
				sprintf(
					'task=%s renders differently without an explicit format. The tail of the format-less response is: %s',
					$task,
					var_export(substr($without->body, -80), true)
				)
			);
		}
	}

	/**
	 * The URLs the JSON stream advertises are usable as-is.
	 *
	 * JSON has no use for HTML entities. An `&amp;`-escaped URL, followed verbatim by a client — which is
	 * the whole point of publishing it — asks for a parameter literally named `amp;view`. This is the
	 * same defect the XML `ref`/`detailsurl` attributes had; the JSON view inherited it through
	 * `Common::getDownloadUrl()`, which must keep escaping for the XML path's sake.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testJsonStreamUrlsAreNotHtmlEscaped(): void
	{
		$items = $this->guest()->get($this->jsonStreamUrl('main'))->json();

		$this->assertNotEmpty($items, 'The JSON update stream is empty.');

		foreach ($items as $item)
		{
			foreach (['download', 'infoUrl'] as $key)
			{
				$this->assertStringNotContainsString(
					'&amp;',
					$item[$key] ?? '',
					sprintf('The JSON stream\'s %s for version %s is HTML-escaped.', $key, $item['version'] ?? '?')
				);
			}
		}

		// The advertised download URL must actually deliver the file.
		$download = $this->guest()->get($items[0]['download']);

		$this->assertStatus(
			200,
			$download,
			'The download URL advertised by the JSON update stream does not deliver the file.'
		);
		$this->assertNotEmpty($download->body, 'The download URL advertised by the JSON update stream delivered nothing.');
	}

	/**
	 * `targetplatform` elements are emitted, maturity appears as a `<tag>`, and checksums are present.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testUpdateElementsCarryTargetplatformMaturityTagAndChecksum(): void
	{
		$response = $this->guest()->get($this->streamUrl('main'));
		$xml      = new SimpleXMLElement($response->body);

		$this->assertGreaterThan(0, count($xml->update), 'The main stream has no <update> elements.');

		foreach ($xml->update as $update)
		{
			$this->assertGreaterThan(0, count($update->targetplatform), 'An <update> element has no <targetplatform> element.');
			$this->assertNotEmpty((string) $update->targetplatform['name'], 'A <targetplatform> element has no name attribute.');
			$this->assertNotEmpty((string) $update->targetplatform['version'], 'A <targetplatform> element has no version attribute.');

			$this->assertGreaterThan(0, count($update->tags->tag), 'An <update> element has no maturity <tag>.');
			$this->assertContains(
				(string) $update->tags->tag,
				['stable', 'rc', 'beta', 'alpha'],
				'The maturity tag is not one of the known ARS maturities.'
			);

			// minify_xml is pinned on by the fixtures, which restricts the emitted checksum to sha512 only.
			$this->assertNotEmpty((string) $update->sha512, 'An <update> element has no <sha512> checksum.');
		}
	}

	/**
	 * A `&dlid=` query parameter is threaded into the `downloadurl` of every `<update>` element.
	 *
	 * REGRESSION GUARD. This used to be a hard 500 on every update-stream request that carried a
	 * `dlid`, whatever the task (`all`, `category` or `stream`) and whatever the format:
	 * `Update\Common::commonSetup()` did `$this->getModel('item')->reformatDownloadID($dlid)`, but the
	 * Update controller never pushes an Item model into its views, so `getModel('item')` returned NULL
	 * (with an "Undefined array key" warning from Joomla's `AbstractView::getModel()`) and the method
	 * call on it was fatal. `commonSetup()` now creates the Item model through the component's MVC
	 * factory instead. All three tasks are exercised here because all three route through the same
	 * `commonSetup()`.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testDlidIsThreadedIntoTheDownloadUrl(): void
	{
		$dlid     = static::$fixtures->dlid('subscriber');
		$response = $this->guest()->get($this->streamUrl('main', ['dlid' => $dlid]));

		$this->assertStatus(200, $response, 'The update stream task "stream" did not render with a dlid.');

		$xml = new SimpleXMLElement($response->body);

		$this->assertGreaterThan(0, count($xml->update), 'The main stream carries no <update> elements, so nothing was checked.');

		foreach ($xml->update as $update)
		{
			$this->assertStringContainsString(
				'dlid=' . $dlid,
				(string) $update->downloads->downloadurl,
				'A download URL does not carry the requested dlid.'
			);
		}
	}

	/**
	 * The `all` and `category` tasks survive a `dlid` too, and thread it into the URLs they point at.
	 *
	 * These two emit an `<extensionset>` of `<category ref="…">` / `<extension detailsurl="…">`
	 * pointers rather than `<update>` elements, so the Download ID shows up in those attributes. They
	 * are covered separately because they crashed for exactly the same reason: all three tasks call
	 * `Update\Common::commonSetup()`.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testDlidIsThreadedIntoTheAllAndCategoryTasks(): void
	{
		$dlid = static::$fixtures->dlid('subscriber');

		$urls = [
			'all'      => $this->siteUrl(['view' => 'update', 'task' => 'all', 'format' => 'xml', 'dlid' => $dlid]),
			// task=category takes an update stream TYPE, not a category id or alias. All fixture streams are 'components'.
			'category' => $this->siteUrl([
				'view'   => 'update',
				'task'   => 'category',
				'format' => 'xml',
				'id'     => 'components',
				'dlid'   => $dlid,
			]),
		];

		foreach ($urls as $task => $url)
		{
			$response = $this->guest()->get($url);

			$this->assertStatus(200, $response, sprintf('The update stream task "%s" did not render with a dlid.', $task));

			$xml      = new SimpleXMLElement($response->body);
			$pointers = $task === 'all' ? $xml->category : $xml->extension;
			$attribute = $task === 'all' ? 'ref' : 'detailsurl';

			$this->assertGreaterThan(
				0,
				count($pointers),
				sprintf('The update stream task "%s" carries no pointer elements, so nothing was checked.', $task)
			);

			foreach ($pointers as $pointer)
			{
				$this->assertStringContainsString(
					'dlid=' . $dlid,
					(string) $pointer[$attribute],
					sprintf('A %s URL from task "%s" does not carry the requested dlid.', $attribute, $task)
				);
			}
		}
	}

	/**
	 * The pointer URLs the `all` and `category` tasks emit are not double-escaped, and they resolve.
	 *
	 * REGRESSION GUARD. Both layouts built the attribute with `Route::_($url, $xhtml = true, …)` and
	 * then handed the result to `SimpleXMLElement::addAttribute()`, which escapes ampersands itself —
	 * so `&` became `&amp;amp;` on the wire and `&amp;` after XML parsing, and a client following the
	 * link asked for a query parameter literally named `amp;view`. (`stream.php` is NOT affected and
	 * is deliberately left alone: it builds its URLs with `addChild()`, which — unlike
	 * `addAttribute()` — does not escape ampersands, so the pre-escaping there is load-bearing.)
	 *
	 * A string search of the raw body cannot see this: `&amp;` is both the correct wire form and the
	 * broken parsed form. The check therefore runs on the value SimpleXML hands back, which is what a
	 * real client would act on, and then actually follows it.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testAllAndCategoryPointerUrlsAreNotDoubleEscaped(): void
	{
		$cases = [
			'all'      => [
				'url'       => $this->siteUrl(['view' => 'update', 'task' => 'all', 'format' => 'xml']),
				'element'   => 'category',
				'attribute' => 'ref',
			],
			// task=category takes an update stream TYPE, not a category id or alias. All fixture streams are 'components'.
			'category' => [
				'url'       => $this->siteUrl(['view' => 'update', 'task' => 'category', 'format' => 'xml', 'id' => 'components']),
				'element'   => 'extension',
				'attribute' => 'detailsurl',
			],
		];

		foreach ($cases as $task => $case)
		{
			$response = $this->guest()->get($case['url']);

			$this->assertStatus(200, $response, sprintf('The update stream task "%s" did not render.', $task));

			$xml      = new SimpleXMLElement($response->body);
			$pointers = $xml->{$case['element']};

			$this->assertGreaterThan(
				0,
				count($pointers),
				sprintf('The update stream task "%s" carries no <%s> elements, so nothing was checked.', $task, $case['element'])
			);

			foreach ($pointers as $pointer)
			{
				$pointerUrl = (string) $pointer[$case['attribute']];

				$this->assertStringNotContainsString(
					'&amp;',
					$pointerUrl,
					sprintf(
						'The %s attribute of a <%s> element is double-escaped: after XML parsing it still reads '
						. '"&amp;" where it should read a bare "&".',
						$case['attribute'],
						$case['element']
					)
				);
			}

			// Following the first pointer must land on a real update document, not an error page.
			$followed = $this->guest()->get((string) $pointers[0][$case['attribute']]);

			$this->assertStatus(
				200,
				$followed,
				sprintf('Following the %s URL from task "%s" did not return an update document.', $case['attribute'], $task)
			);
			$this->assertStringStartsWith(
				'<?xml',
				trim($followed->body),
				sprintf('Following the %s URL from task "%s" did not return XML.', $case['attribute'], $task)
			);
		}
	}

	/**
	 * A secondary Download ID keeps its `userId:downloadId` shape all the way into the download URL.
	 *
	 * REGRESSION GUARD for the second half of the same bug. `commonSetup()` read the parameter with
	 * `getCmd()`, whose filter strips the colon — the only thing that tells a secondary Download ID
	 * apart from a primary one. `669:b0bb…` silently became `669b0bb…`, which `reformatDownloadID()`
	 * then truncated to its first 32 characters, so the stream advertised a download URL carrying a
	 * Download ID that belongs to nobody. Every other place ARS reads `dlid` uses `STRING`
	 * ({@see \Akeeba\Component\ARS\Site\Controller\UpdateController} registers it as such for page
	 * caching, and `ItemModel` reads it with `getString()`), so `commonSetup()` now does too.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testSecondaryDownloadIdKeepsItsUserIdPrefix(): void
	{
		$dlid     = sprintf('%u:%s', static::$fixtures->userId('subscriber'), static::$fixtures->dlid('subscriberSecondary'));
		$response = $this->guest()->get($this->streamUrl('main', ['dlid' => $dlid]));

		$this->assertStatus(200, $response, 'The update stream did not render with a userId-prefixed dlid.');

		$xml = new SimpleXMLElement($response->body);

		$this->assertGreaterThan(0, count($xml->update), 'The main stream carries no <update> elements.');

		foreach ($xml->update as $update)
		{
			$this->assertStringContainsString(
				'dlid=' . $dlid,
				(string) $update->downloads->downloadurl,
				'A download URL dropped the userId prefix of the secondary Download ID.'
			);
		}
	}

	/**
	 * A malformed Download ID is dropped rather than propagated into the download URLs.
	 *
	 * `reformatDownloadID()` returns an empty string for anything shorter than 32 characters, and
	 * `commonSetup()` must then emit no `dlid` at all — not `dlid=`, and certainly not the garbage it
	 * was handed.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testMalformedDlidIsDroppedFromTheDownloadUrl(): void
	{
		$response = $this->guest()->get($this->streamUrl('main', ['dlid' => 'not-a-download-id']));

		$this->assertStatus(200, $response, 'The update stream did not render with a malformed dlid.');
		$this->assertStringNotContainsString(
			'dlid',
			$response->body,
			'The update stream propagated a malformed Download ID into its output instead of dropping it.'
		);
	}

	/**
	 * The download URL a dlid-carrying stream advertises actually works.
	 *
	 * The `restrictedStream` points at the `restricted` category, which a guest cannot download from.
	 * Following the advertised URL as a guest must therefore succeed *because of* the threaded
	 * Download ID — and the same URL with the `dlid` stripped off must be refused. That pair is what
	 * proves the feature end to end rather than merely proving a string got interpolated.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testTheAdvertisedDlidDownloadUrlActuallyDownloads(): void
	{
		$dlid     = static::$fixtures->dlid('subscriber');
		$response = $this->guest()->get($this->streamUrl('restrictedStream', ['dlid' => $dlid]));

		$this->assertStatus(200, $response, 'The restricted update stream did not render with a dlid.');

		$xml = new SimpleXMLElement($response->body);

		$this->assertGreaterThan(0, count($xml->update), 'The restricted update stream carries no <update> elements.');

		$downloadUrl = (string) $xml->update[0]->downloads->downloadurl;

		$this->assertStringContainsString('dlid=' . $dlid, $downloadUrl, 'The advertised download URL carries no dlid.');

		$withDlid    = $this->guest()->get($downloadUrl);
		$withoutDlid = $this->guest()->get(str_replace('&dlid=' . $dlid, '', $downloadUrl));

		$this->assertStatus(200, $withDlid, 'The download URL the stream advertised for a valid Download ID was refused.');
		$this->assertNotEmpty($withDlid->body, 'The download URL the stream advertised returned an empty body.');
		$this->assertStatus(
			403,
			$withoutDlid,
			'The same restricted download succeeded without the Download ID, so the previous assertion proves nothing.'
		);
	}

	/**
	 * An unpublished update stream yields no updates at all.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testUnpublishedStreamYieldsNoUpdates(): void
	{
		$response = $this->guest()->get($this->streamUrl('unpublishedStream'));

		$this->assertStatus(200, $response, 'The unpublished update stream did not render.');

		$dom = new DOMDocument();
		$this->assertTrue($dom->loadXML($response->body), "The unpublished stream's response body is not well-formed XML.");

		$this->assertSame(
			0,
			$dom->getElementsByTagName('update')->length,
			'The unpublished update stream carries at least one <update> element.'
		);
	}

	/**
	 * Items from the `restricted` category appear in the update stream identically for a guest and a
	 * subscriber.
	 *
	 * This is deliberately NOT asserted as "guest cannot see it, subscriber can" — that would be
	 * asserting from reading the code rather than from what the site does. Confirmed empirically:
	 * `UpdateModel::getItems()`'s query filters only on `published` flags (stream, item, release,
	 * category); it has no `WHERE ... access IN (...)` clause at all, so the ARS view-level access
	 * control that gates the front-end browsing and download views does not apply to update streams.
	 * A guest and a subscriber get byte-identical `<update>` content for the restrictedStream. Whether
	 * that is intentional (update servers are typically consulted by software that already has a
	 * legitimate copy, not by a browsing visitor) or an oversight is a product question, not something
	 * this suite can decide — it is recorded here as the actual, current behaviour.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testRestrictedCategoryItemsAppearIdenticallyForGuestAndSubscriber(): void
	{
		$guestResponse      = $this->guest()->get($this->streamUrl('restrictedStream'));
		$subscriberResponse = $this->loggedIn('subscriber')->get($this->streamUrl('restrictedStream'));

		$this->assertStatus(200, $guestResponse, 'The restricted update stream did not render for a guest.');
		$this->assertStatus(200, $subscriberResponse, 'The restricted update stream did not render for a subscriber.');

		$guestXml      = new SimpleXMLElement($guestResponse->body);
		$subscriberXml = new SimpleXMLElement($subscriberResponse->body);

		$this->assertGreaterThan(0, count($guestXml->update), 'The restricted update stream is empty for a guest.');
		$this->assertSame(
			count($subscriberXml->update),
			count($guestXml->update),
			'A guest and a subscriber see a different number of <update> elements for the restricted stream, '
			. 'which would contradict the finding that update streams are not access-controlled.'
		);
		$this->assertSame(
			(string) $guestXml->update[0]->version,
			(string) $subscriberXml->update[0]->version,
			'A guest and a subscriber see different content for the restricted update stream.'
		);
	}

	/**
	 * Build an update-stream URL for a fixture stream.
	 *
	 * @param   string  $streamKey  An update-stream name from the fixture manifest.
	 * @param   array   $extra      Extra query parameters.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	private function streamUrl(string $streamKey, array $extra = []): string
	{
		return $this->siteUrl(array_merge(
			[
				'view'   => 'update',
				'task'   => 'stream',
				'format' => 'xml',
				'id'     => static::$fixtures->updateStreamId($streamKey),
			],
			$extra
		));
	}

	/**
	 * Build a JSON update-stream URL for a fixture stream.
	 *
	 * @param   string  $streamKey  An update-stream name from the fixture manifest.
	 * @param   array   $extra      Extra query parameters.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	private function jsonStreamUrl(string $streamKey, array $extra = []): string
	{
		return $this->siteUrl(array_merge(
			[
				'view' => 'update',
				'task' => 'json',
				'id'   => static::$fixtures->updateStreamId($streamKey),
			],
			$extra
		));
	}
}

<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Tests;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\AbstractE2ETestCase;
use Akeeba\ARS\IntegrationTest\Engine\Response;
use Akeeba\ARS\IntegrationTest\Engine\Surfer;
use PHPUnit\Framework\Attributes\Group;

/**
 * Appending `&dlid=<id>` to a download request logs a guest in for the duration of that request —
 * `ItemModel::loginUser()` reads it, resolves it with `getUserFromDownloadID()`, and fakes a login
 * response for whichever user owns it. Every case here downloads `restrictedFile`, which needs the
 * ARS Subscribers view level, as a guest with only the `dlid` query parameter to authenticate.
 *
 * @since 7.5.0
 */
#[Group('download')]
#[Group('authorisation')]
class DownloadIdTest extends AbstractE2ETestCase
{
	/**
	 * The subscriber's own primary Download ID authenticates them and they can download a file gated
	 * on the Subscribers view level.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testPrimaryDownloadIdAuthorisesTheRestrictedFile(): void
	{
		$response = $this->downloadRestrictedFileWithDlid(static::$fixtures->dlid('subscriber'));

		$this->assertStatus(200, $response, "The subscriber's own primary Download ID did not authorise the download.");
		$this->assertBodyContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$response,
			'The download was reported as successful but the file contents are missing.'
		);
	}

	/**
	 * A secondary Download ID, in the `userid:dlid` form, also authorises the download.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testSecondaryDownloadIdInUseridPrefixedFormAuthorisesTheRestrictedFile(): void
	{
		$dlid     = static::$fixtures->userId('subscriber') . ':' . static::$fixtures->dlid('subscriberSecondary');
		$response = $this->downloadRestrictedFileWithDlid($dlid);

		$this->assertStatus(200, $response, "The subscriber's userid-prefixed secondary Download ID did not authorise the download.");
		$this->assertBodyContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$response,
			'The download was reported as successful but the file contents are missing.'
		);
	}

	/**
	 * The SAME secondary Download ID, without its `userid:` prefix, does NOT authorise the download.
	 *
	 * `getUserFromDownloadID()` looks up a bare (unprefixed) Download ID as `primary = 1`. The
	 * secondary label is stored with `primary = 0`, so the lookup finds no matching row and the
	 * request is refused — confirmed empirically (HTTP 403, no bytes), not inferred from the model.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testSecondaryDownloadIdWithoutItsUseridPrefixIsRefused(): void
	{
		$response = $this->downloadRestrictedFileWithDlid(static::$fixtures->dlid('subscriberSecondary'));

		$this->assertRefused($this->guest(), $response, 'A secondary Download ID authorised a download without its owning userid prefix.');
		$this->assertBodyNotContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$response,
			'The request was refused, but the file contents came back anyway.'
		);
	}

	/**
	 * An unpublished (revoked) Download ID does not authenticate anybody.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testRevokedDownloadIdIsRefused(): void
	{
		$response = $this->downloadRestrictedFileWithDlid(static::$fixtures->dlid('subscriberRevoked'));

		$this->assertRefused($this->guest(), $response, 'A revoked (unpublished) Download ID authorised a download.');
		$this->assertBodyNotContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$response,
			'The request was refused, but the file contents came back anyway.'
		);
	}

	/**
	 * The `client` Download ID is valid and belongs to a real, published label — so `loginUser()`
	 * really does log the guest in as `client` — but `client` does not hold the ARS Subscribers view
	 * level, so the subsequent access-control check in `ControllerCRIAccessTrait` still refuses the
	 * download. Authentication and authorisation are different things, and this is the case that
	 * proves it.
	 *
	 * Proven, not asserted: the download log row for this attempt (`#__ars_log`, populated by
	 * `LogTable::onBeforeCheck()` from the CURRENT identity, which is written before
	 * `ItemModel::logoutUser()` un-does the fake login) carries `client`'s own user id, not 0/guest —
	 * unlike the same log row for a Download ID that never matches anybody at all (see
	 * {@see testSyntacticallyValidButUnknownDownloadIdIsRefused()}). That is the observable difference
	 * between "authenticated, then refused" and "never authenticated".
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testClientDownloadIdAuthenticatesButIsNotAuthorised(): void
	{
		$itemId   = static::$fixtures->itemId('restrictedFile');
		$response = $this->downloadRestrictedFileWithDlid(static::$fixtures->dlid('client'));

		$this->assertRefused($this->guest(), $response, "The client's Download ID authorised a download it should not have access to.");
		$this->assertBodyNotContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$response,
			'The request was refused, but the file contents came back anyway.'
		);

		$logRow = $this->db()->row(
			'SELECT `user_id` FROM `#__ars_log` WHERE `item_id` = ? ORDER BY `id` DESC LIMIT 1',
			[$itemId]
		);

		$this->assertIsArray($logRow, 'No download-attempt log row was written for the client Download ID attempt.');
		$this->assertSame(
			static::$fixtures->userId('client'),
			(int) $logRow['user_id'],
			"The failed-download log entry was not attributed to 'client', so loginUser() did not actually "
			. 'authenticate them before the access check refused the download.'
		);
	}

	/**
	 * A syntactically valid but unknown Download ID never matches anybody, so `loginUser()` never logs
	 * anybody in (the log row for the attempt carries user id 0, i.e. guest) and the download is
	 * refused.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testSyntacticallyValidButUnknownDownloadIdIsRefused(): void
	{
		$itemId   = static::$fixtures->itemId('restrictedFile');
		$response = $this->downloadRestrictedFileWithDlid(str_repeat('a', 32));

		$this->assertRefused($this->guest(), $response, 'A syntactically valid but unknown Download ID authorised a download.');
		$this->assertBodyNotContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$response,
			'The request was refused, but the file contents came back anyway.'
		);

		$logRow = $this->db()->row(
			'SELECT `user_id` FROM `#__ars_log` WHERE `item_id` = ? ORDER BY `id` DESC LIMIT 1',
			[$itemId]
		);

		$this->assertIsArray($logRow, 'No download-attempt log row was written for the unknown Download ID attempt.');
		$this->assertSame(
			0,
			(int) $logRow['user_id'],
			'An unknown Download ID left a log entry attributed to a real user, so it authenticated somebody it should not have.'
		);
	}

	/**
	 * The `other` user's Download ID, paired with `subscriber`'s user id, does not authorise the
	 * download: the label lookup requires the dlid AND the user id to belong to the SAME record.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testAnotherUsersDownloadIdPairedWithADifferentUseridIsRefused(): void
	{
		$dlid     = static::$fixtures->userId('subscriber') . ':' . static::$fixtures->dlid('other');
		$response = $this->downloadRestrictedFileWithDlid($dlid);

		$this->assertRefused($this->guest(), $response, "Another user's Download ID, paired with a different user's id, authorised a download.");
		$this->assertBodyNotContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$response,
			'The request was refused, but the file contents came back anyway.'
		);
	}

	/**
	 * A too-short Download ID is refused.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testTooShortDownloadIdIsRefused(): void
	{
		$response = $this->downloadRestrictedFileWithDlid('abc123');

		$this->assertRefused($this->guest(), $response, 'A too-short Download ID authorised a download.');
		$this->assertBodyNotContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$response,
			'The request was refused, but the file contents came back anyway.'
		);
	}

	/**
	 * An empty Download ID is refused (and simply ignored — `loginUser()` treats it as "no dlid at
	 * all", so the request proceeds as an ordinary anonymous guest download).
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testEmptyDownloadIdIsRefused(): void
	{
		$response = $this->downloadRestrictedFileWithDlid('');

		$this->assertRefused($this->guest(), $response, 'An empty Download ID authorised a download.');
		$this->assertBodyNotContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$response,
			'The request was refused, but the file contents came back anyway.'
		);
	}

	/**
	 * A bogus, non-numeric-looking `userid:` prefix is refused rather than causing an error. Note that
	 * `reformatDownloadID()` casts the prefix with `(int) $parts[0]`, so a non-numeric prefix such as
	 * `bogus:<dlid>` is silently coerced to user id 0, not rejected outright — either way, the record
	 * lookup for user id 0 (or an unrelated numeric id) finds nothing and the download is refused.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testBogusUseridPrefixIsRefused(): void
	{
		$dlid     = '999999:' . static::$fixtures->dlid('subscriber');
		$response = $this->downloadRestrictedFileWithDlid($dlid);

		$this->assertRefused($this->guest(), $response, 'A bogus userid: prefix authorised a download.');
		$this->assertBodyNotContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$response,
			'The request was refused, but the file contents came back anyway.'
		);
	}

	/**
	 * A Download ID does not leak a category the owning user cannot see. The subscriber's Download ID
	 * authorises `restrictedFile` (Subscribers view level) but must not reach into `secretFile`
	 * (Secret view level, which the subscriber does not hold).
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testSubscriberDownloadIdDoesNotLeakTheSecretCategory(): void
	{
		$guest    = $this->guest();
		$itemId   = static::$fixtures->itemId('secretFile');
		$response = $guest->get($this->siteUrl([
			'view'    => 'item',
			'task'    => 'download',
			'format'  => 'raw',
			'item_id' => $itemId,
			'dlid'    => static::$fixtures->dlid('subscriber'),
		]));

		$this->assertRefused($guest, $response, "The subscriber's Download ID reached into the secret category.");
		$this->assertBodyNotContains(
			static::$fixtures->file('secretFile')['sentinel'],
			$response,
			'The request was refused, but the secret file contents came back anyway.'
		);
	}

	/**
	 * A refused `update.download` is refused properly: HTTP 403, not a crash in the denial logger, no file
	 * bytes, and a log entry for the denial (gh-255).
	 *
	 * @return  void
	 * @since   7.5.2
	 */
	public function testDeniedUpdateStreamDownloadIsRefusedWith403AndLogged(): void
	{
		$itemId    = static::$fixtures->itemId('restrictedFile');
		$lastLogId = (int) ($this->db()->row('SELECT MAX(`id`) AS `id` FROM `#__ars_log`')['id'] ?? 0);
		$response  = $this->downloadRestrictedStreamWithDlid($this->guest(), static::$fixtures->dlid('client'));

		$this->assertStatus(403, $response, 'A refused update stream download did not answer with HTTP 403.');
		$this->assertBodyNotContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$response,
			'The request was refused, but the file contents came back anyway.'
		);

		$logRow = $this->db()->row(
			'SELECT `user_id`, `authorized` FROM `#__ars_log` WHERE `item_id` = ? AND `id` > ? ORDER BY `id` DESC LIMIT 1',
			[$itemId, $lastLogId]
		);

		$this->assertIsArray($logRow, 'No download log entry was written for the refused update stream download.');
		$this->assertSame(0, (int) $logRow['authorized'], 'The refused download was not logged as a denial.');
		$this->assertSame(
			static::$fixtures->userId('client'),
			(int) $logRow['user_id'],
			'The denial was not attributed to the owner of the Download ID.'
		);
	}

	/**
	 * A Download ID authenticates one request only. After a refused `update.download` the session cookie
	 * from that response must not be logged in as the Download ID's owner (gh-255).
	 *
	 * @return  void
	 * @since   7.5.2
	 */
	public function testDeniedUpdateStreamDownloadLeavesNoLoggedInSession(): void
	{
		$surfer = $this->guest();

		$this->downloadRestrictedStreamWithDlid($surfer, static::$fixtures->dlid('client'));

		$this->assertSessionIsGuest($surfer);
	}

	/**
	 * The allowed counterpart: `update.download` serves the file to an entitled Download ID, and does not
	 * leave a logged-in session behind either.
	 *
	 * @return  void
	 * @since   7.5.2
	 */
	public function testAllowedUpdateStreamDownloadServesTheFileAndLeavesNoLoggedInSession(): void
	{
		$surfer   = $this->guest();
		$response = $this->downloadRestrictedStreamWithDlid($surfer, static::$fixtures->dlid('subscriber'));

		$this->assertStatus(200, $response, 'An entitled Download ID could not download through the update stream.');
		$this->assertBodyContains(
			static::$fixtures->file('restrictedFile')['sentinel'],
			$response,
			'The download was reported as successful but the file contents are missing.'
		);
		$this->assertSessionIsGuest($surfer);
	}

	/**
	 * Download the latest file of the restricted update stream, with a `dlid` query parameter.
	 *
	 * @param   Surfer  $surfer  The browser session making the request.
	 * @param   string  $dlid    The Download ID to send.
	 *
	 * @return  Response
	 * @since   7.5.2
	 */
	private function downloadRestrictedStreamWithDlid(Surfer $surfer, string $dlid): Response
	{
		return $surfer->get($this->siteUrl([
			'view'   => 'update',
			'task'   => 'download',
			'format' => 'raw',
			'id'     => static::$fixtures->updateStreamId('restrictedStream'),
			'dlid'   => $dlid,
		]));
	}

	/**
	 * Assert that a surfer's cookies, sent without any Download ID, belong to a guest.
	 *
	 * @param   Surfer  $surfer  The browser session to check.
	 *
	 * @return  void
	 * @since   7.5.2
	 */
	private function assertSessionIsGuest(Surfer $surfer): void
	{
		$identity = $this->session->probeIdentity($surfer);

		$this->assertIsArray($identity, 'The identity probe did not answer.');
		$this->assertTrue(
			$identity['guest'],
			sprintf('The session was left logged in as "%s".', $identity['username'])
		);
	}

	/**
	 * Download `restrictedFile` as a guest, with a `dlid` query parameter.
	 *
	 * @param   string  $dlid  The Download ID (or garbage) to send.
	 *
	 * @return  Response
	 * @since   7.5.0
	 */
	private function downloadRestrictedFileWithDlid(string $dlid): Response
	{
		$itemId = static::$fixtures->itemId('restrictedFile');

		return $this->guest()->get($this->siteUrl([
			'view'    => 'item',
			'task'    => 'download',
			'format'  => 'raw',
			'item_id' => $itemId,
			'dlid'    => $dlid,
		]));
	}
}

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
 * The `v1/ars/dlidlabels` endpoint is authorised differently from every other ARS API endpoint.
 *
 * A Download ID is a bearer credential, so the endpoint deliberately does NOT sit behind a blanket core.manage
 * check the way the other read endpoints do. Instead any authenticated user may see their own records — mirroring
 * the frontend Download IDs view — while seeing anybody else's requires core.manage.
 *
 * That split only works because DlidlabelsController re-imposes the ownership filter the Administrator list model
 * does not apply outside the site application. This class is the regression test for that: the model on its own
 * would happily return every row in `#__ars_dlidlabels` to any token that reaches it.
 *
 * @since 7.5.1
 */
#[Group('api')]
#[Group('authorisation')]
class ApiDlidlabelAuthorisationTest extends AbstractE2ETestCase
{
	/**
	 * A guest — no token at all — cannot reach the endpoint.
	 *
	 * @since 7.5.1
	 */
	public function testUnauthenticatedRequestIsRefused(): void
	{
		$response = $this->api('GET', 'v1/ars/dlidlabels', null);

		$this->assertNotSame(
			200,
			$response->code,
			'An unauthenticated request was able to list Download ID labels.'
		);
	}

	/**
	 * A subscriber's list contains their own records and nothing else, however many rows exist.
	 *
	 * @since 7.5.1
	 */
	public function testSubscriberOnlySeesTheirOwnRecords(): void
	{
		$subscriberId = static::$fixtures->userId('subscriber');
		$response     = $this->api('GET', 'v1/ars/dlidlabels?page[limit]=100', static::$fixtures->apiToken('subscriber'));

		$this->assertStatus(200, $response, 'A subscriber token could not list its own Download ID labels.');

		$data = (array) ($response->json()['data'] ?? []);

		$this->assertNotEmpty($data, 'The subscriber got an empty list, so the assertions below prove nothing.');

		foreach ($data as $record)
		{
			$this->assertSame(
				$subscriberId,
				(int) ($record['attributes']['user_id'] ?? 0),
				"A subscriber's Download ID label list contained a record belonging to somebody else."
			);
		}
	}

	/**
	 * The `user_id` filter cannot be used to page into another user's records: it is overwritten, not honoured.
	 *
	 * @since 7.5.1
	 */
	public function testSubscriberCannotFilterByAnotherUserId(): void
	{
		$otherId  = static::$fixtures->userId('other');
		$response = $this->api(
			'GET',
			'v1/ars/dlidlabels?user_id=' . $otherId,
			static::$fixtures->apiToken('subscriber')
		);

		$this->assertStatus(200, $response, 'A subscriber token could not list its own Download ID labels.');

		$data = (array) ($response->json()['data'] ?? []);

		foreach ($data as $record)
		{
			$this->assertNotSame(
				$otherId,
				(int) ($record['attributes']['user_id'] ?? 0),
				'The user_id filter let a subscriber read another user\'s Download ID labels.'
			);
		}

		$this->assertBodyNotContains(
			static::$fixtures->dlid('other'),
			$response,
			"The response disclosed another user's Download ID string."
		);
	}

	/**
	 * The `dlid` filter understands a `<user_id>:<dlid>` string. That user ID must not override the ownership
	 * filter the controller imposed — it is the exact shape which would leak a record if the model preferred it.
	 *
	 * @since 7.5.1
	 */
	public function testSubscriberCannotUseTheDlidFilterToReachAnotherUser(): void
	{
		$otherDlid = static::$fixtures->dlid('other');
		$otherId   = static::$fixtures->userId('other');

		$response = $this->api(
			'GET',
			'v1/ars/dlidlabels?dlid=' . rawurlencode($otherId . ':' . $otherDlid),
			static::$fixtures->apiToken('subscriber')
		);

		$this->assertStatus(200, $response, 'A subscriber token could not list its own Download ID labels.');

		/**
		 * Only `data` is checked here, deliberately. JSON:API echoes the request URI back in `links.self`, so the
		 * Download ID we just asked about is in the body no matter what — it came from us, not from the database.
		 */
		$this->assertSame(
			[],
			(array) ($response->json()['data'] ?? []),
			"The dlid filter returned another user's Download ID label to a subscriber."
		);
	}

	/**
	 * Reading a single record belonging to somebody else is refused.
	 *
	 * @since 7.5.1
	 */
	public function testSubscriberCannotReadAnotherUsersRecord(): void
	{
		$otherLabelId = static::$fixtures->dlidLabelId('otherPrimary');
		$response     = $this->api(
			'GET',
			'v1/ars/dlidlabels/' . $otherLabelId,
			static::$fixtures->apiToken('subscriber')
		);

		$this->assertStatus(
			403,
			$response,
			"A subscriber token was able to read another user's Download ID label."
		);
		$this->assertBodyNotContains(
			static::$fixtures->dlid('other'),
			$response,
			"The response disclosed another user's Download ID string."
		);
	}

	/**
	 * The control: a subscriber CAN read their own record. Without this the refusals above would be
	 * indistinguishable from the endpoint being broken for everyone.
	 *
	 * @since 7.5.1
	 */
	public function testSubscriberCanReadTheirOwnRecord(): void
	{
		$ownLabelId = static::$fixtures->dlidLabelId('subscriberSecondary');
		$response   = $this->api(
			'GET',
			'v1/ars/dlidlabels/' . $ownLabelId,
			static::$fixtures->apiToken('subscriber')
		);

		$this->assertStatus(200, $response, 'A subscriber token could not read its own Download ID label.');
		$this->assertSame(
			static::$fixtures->userId('subscriber'),
			(int) ($response->json()['data']['attributes']['user_id'] ?? 0),
			'The record the subscriber read back is not the one that was asked for.'
		);
	}

	/**
	 * A manager — core.manage, but not core.admin — may read across users, exactly as they can in the back-end.
	 *
	 * @since 7.5.1
	 */
	public function testManagerCanReadAnotherUsersRecord(): void
	{
		$otherLabelId = static::$fixtures->dlidLabelId('otherPrimary');
		$response     = $this->api(
			'GET',
			'v1/ars/dlidlabels/' . $otherLabelId,
			static::$fixtures->apiToken('manager')
		);

		$this->assertStatus(
			200,
			$response,
			'A manager token could not read a Download ID label, so the 403s above do not prove anything.'
		);
	}

	/**
	 * A subscriber may not hand their record to another user, nor edit somebody else's.
	 *
	 * @since 7.5.1
	 */
	public function testSubscriberCannotWriteOutsideTheirOwnRecords(): void
	{
		$subscriberToken = static::$fixtures->apiToken('subscriber');
		$otherLabelId    = static::$fixtures->dlidLabelId('otherPrimary');
		$ownLabelId      = static::$fixtures->dlidLabelId('subscriberSecondary');
		$otherUserId     = static::$fixtures->userId('other');

		$response = $this->api('PATCH', 'v1/ars/dlidlabels/' . $otherLabelId, $subscriberToken, ['published' => 0]);

		$this->assertStatus(
			403,
			$response,
			"A subscriber token was able to edit another user's Download ID label."
		);

		$response = $this->api(
			'PATCH',
			'v1/ars/dlidlabels/' . $ownLabelId,
			$subscriberToken,
			['user_id' => $otherUserId]
		);

		$this->assertStatus(
			403,
			$response,
			'A subscriber token was able to reassign its own Download ID label to another user.'
		);

		$response = $this->api(
			'POST',
			'v1/ars/dlidlabels',
			$subscriberToken,
			['user_id' => $otherUserId, 'title' => 'E2E-WRITE-TEST should never exist', 'published' => 1]
		);

		$this->assertStatus(
			403,
			$response,
			'A subscriber token was able to create a Download ID label for another user.'
		);
	}

	/**
	 * One small helper for a raw JSON:API request: a fresh Surfer, no cookies, the vnd.api+json Accept header,
	 * and the token (if any) on X-Joomla-Token.
	 *
	 * @param   string       $verb   HTTP verb.
	 * @param   string       $path   Path under api/index.php, e.g. 'v1/ars/dlidlabels'.
	 * @param   string|null  $token  A Joomla API token, or null for an unauthenticated request.
	 * @param   array|null   $body   Request body, JSON-encoded. Null for no body.
	 *
	 * @return  Response
	 * @since   7.5.1
	 */
	private function api(string $verb, string $path, ?string $token, ?array $body = null): Response
	{
		$surfer  = new Surfer(static::$config->getSiteUrl());
		$headers = ['Accept' => 'application/vnd.api+json'];

		if ($token !== null)
		{
			$headers['X-Joomla-Token'] = $token;
		}

		if ($body !== null)
		{
			$headers['Content-Type'] = 'application/json';

			return $surfer->request($verb, $this->apiUrl($path), json_encode($body), $headers);
		}

		return $surfer->request($verb, $this->apiUrl($path), null, $headers);
	}
}

<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

trait ControllerReturnURLTrait
{
	/** @inheritdoc */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$returnUrl = $this->getReturnUrl();

		if ($returnUrl)
		{
			$this->input->set('return', base64_encode($returnUrl));
		}

		return parent::getRedirectToItemAppend($recordId, $urlVar);
	}

	/** @inheritdoc */
	protected function getRedirectToListAppend()
	{
		$returnUrl = $this->getReturnUrl();

		if ($returnUrl)
		{
			$this->input->set('return', base64_encode($returnUrl));
		}

		return parent::getRedirectToListAppend();
	}

	/**
	 * Redirects to return URL, if one is defined and internal to this site.
	 *
	 * You need to set up the appropriate `onAfterTaskname` events to call this method.
	 *
	 * @since  5.0.0
	 * @see    self::getReturnUrl
	 */
	protected function applyReturnUrl(): void
	{
		$returnUrl = $this->getReturnUrl();

		if (is_null($returnUrl))
		{
			return;
		}

		$this->setRedirect($returnUrl);
	}

	/**
	 * Gets the decoded return URL based on the base64–encoded `returnurl` query string parameter.
	 *
	 * @return  string|null  The URL. NULL if there is none or if it's not an internal URL to this site.
	 *
	 * @since   5.0.0
	 */
	protected function getReturnUrl(): ?string
	{
		$returnEncoded = $this->input->getBase64('return', '');
		$returnEncoded = $this->input->getBase64('returnurl', $returnEncoded);

		if (empty($returnEncoded))
		{
			return null;
		}

		$returnUrl = \base64_decode($returnEncoded);

		// Control characters (especially CR/LF) in a redirect target are a header-injection primitive; reject them outright.
		if ($returnUrl === '' || preg_match('/[\x00-\x1F\x7F]/', $returnUrl))
		{
			return null;
		}

		if (!Uri::isInternal($returnUrl))
		{
			return null;
		}

		return $returnUrl;
	}
}
<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * User information display field
 * Use it $this->loadAnyTemplate('admin:com_ats/Common/ShowUser', $params)
 *
 * $params is an array defining the following keys (they are expanded into local scope vars automatically):
 *
 * @var \FOF30\Model\DataModel   $item  The current row
 * @var string                   $field The name of the field in the current row containing the value
 * @var string                   $id    The ID of the generated DIV
 * @var string                   $showUsername
 * @var string                   $showEmail
 * @var string                   $showName
 * @var string                   $showID
 * @var string                   $showAvatar
 * @var string                   $showLink
 * @var string                   $linkURL
 * @var string                   $avatarMethod
 * @var string                   $avatarSize
 * @var string                   $class
 *
 * Variables made automatically available to us by FOF:
 *
 * @var \FOF30\View\DataView\Raw $this
 */

use FOF30\Utils\FEFHelper\BrowseView;

defined('_JEXEC') or die;

global $atsShowUserCache;

if (!isset($atsShowUserCache))
{
	$atsShowUserCache = [];
}

// Get field parameters
$defaultParams = [
	'id'           => '',
	'showUsername' => true,
	'showEmail'    => true,
	'showName'     => true,
	'showID'       => true,
	'showAvatar'   => true,
	'showLink'     => true,
	'linkURL'      => null,
	'avatarMethod' => 'gravatar',
	'avatarSize'   => 64,
	'class'        => '',
];

foreach ($defaultParams as $paramName => $paramValue)
{
	if (!isset(${$paramName}))
	{
		${$paramName} = $paramValue;
	}
}

unset($defaultParams, $paramName, $paramValue);

// Initialization
$value = $item->getFieldValue($field);
$key   = is_numeric($value) ? $value : 'empty';

// Get the user
if (!array_key_exists($key, $atsShowUserCache))
{
	$atsShowUserCache[$key] = $this->getContainer()->platform->getUser($value);
}

$user = $atsShowUserCache[$key];

// Get the field parameters
if ($avatarMethod)
{
	$avatarMethod = strtolower($avatarMethod);
}

if (!$linkURL && $this->getContainer()->platform->isBackend())
{
	$linkURL = 'index.php?option=com_users&task=user.edit&id=[USER:ID]';
}
elseif (!$linkURL)
{
	// If no link is defined in the front-end, we can't create a default link. Therefore, show no link.
	$showLink = false;
}

// Post-process the link URL
if ($showLink)
{
	$replacements = array(
		'[USER:ID]'       => $user->id,
		'[USER:USERNAME]' => $user->username,
		'[USER:EMAIL]'    => $user->email,
		'[USER:NAME]'     => $user->name,
	);

	foreach ($replacements as $key => $value)
	{
		$linkURL = str_replace($key, $value, $linkURL);
	}

	$linkURL = BrowseView::parseFieldTags($linkURL, $item);
}

// Get the avatar image, if necessary
$avatarURL = '';

if ($showAvatar)
{
	$avatarURL = '';

	if ($avatarMethod == 'plugin')
	{
		// Use the user plugins to get an avatar
		$this->getContainer()->platform->importPlugin('user');
		$jResponse = $this->getContainer()->platform->runPlugins('onUserAvatar', array($user, $avatarSize));

		if (!empty($jResponse))
		{
			foreach ($jResponse as $response)
			{
				if ($response)
				{
					$avatarURL = $response;
				}
			}
		}
	}

	// Fall back to the Gravatar method
	if (empty($avatarURL))
	{
		$md5 = md5($user->email);

		$avatarURL = 'https://secure.gravatar.com/avatar/' . $md5 . '.jpg?s='
			. $avatarSize . '&d=mm';
	}
}

?>
<div id="{{ $id }}" class="{{ $class }}">
    @if($showAvatar)
        <img src="{{ $avatarURL }}" align="left" class="fof-usersfield-avatar" />
    @endif
    @if($showLink)
        <a href="{{ $linkURL }}">
            @endif
            @if($showUsername)
                <span class="fof-usersfield-username">
            {{{ $user->username }}}
        </span>
            @endif
            @if($showID)
                <span class="fof-usersfield-id">
            {{{ $user->id }}}
        </span>
            @endif
            @if($showName)
                <span class="fof-usersfield-name">
            {{{ $user->name }}}
        </span>
            @endif
            @if($showEmail)
                <span class="fof-usersfield-email">
            {{{ $user->email }}}
        </span>
            @endif
            @if($showLink)
        </a>
    @endif
</div>

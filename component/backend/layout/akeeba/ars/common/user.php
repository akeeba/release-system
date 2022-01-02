<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

/**
 * @var array  $displayData  Incoming display data. These set the following variables.
 * @var int    $user_id      User ID. Required when showLink is true.
 * @var string $username     Username. Required for $showUsername.
 * @var string $name         Full name. Required for $showName.
 * @var string $email        Email address. Required for $showEmail and $showGravatar.
 * @var bool   $showUsername Should I display the username?
 * @var bool   $showLink     Should I make the username linked? Requires $showUsername.
 * @var string $link         The link to the username. Use [USER_ID], [USERNAME], [NAME] or [EMAIL] in the link.
 * @var bool   $showName     Should I show the full name?
 * @var bool   $showEmail    Should I show the email address?
 * @var bool   $showUserId   Should I show the user ID?
 * @var bool   $showGravatar Should I show the Gravatar of the user? Requires $email.
 * @var int    $gravatarSize Gravatar size in pixels. Default: 48.
 */

extract(array_merge([
	'user_id'      => 0,
	'username'     => '',
	'name'         => '',
	'email'        => '',
	'showUsername' => true,
	'showLink'     => false,
	'link'         => 'index.php?option=com_users&task=user.edit&id=[USER_ID]',
	'showName'     => true,
	'showEmail'    => true,
	'showUserId'   => false,
	'showGravatar' => true,
	'gravatarSize' => 48,
], $displayData));

$showUsername = $showUsername && !empty($username);
$showLink     = $showLink && $showUsername;
$showName     = $showName && !empty($name);
$showEmail    = $showEmail && !empty($email);
$showUserId   = $showUserId && !empty($user_id);
$showGravatar = $showGravatar && !empty($email);

$link = $showLink
	? str_replace(['[USER_ID]', '[USERNAME]', '[NAME]', '[EMAIL]'], [$user_id, $username, $name, $email], $link)
	: '';

$gravatarUrl = sprintf('https://www.gravatar.com/avatar/%s?s=%s', md5(strtolower(trim($email))), $gravatarSize)

?>
<?php if ($showGravatar && !$showName && !$showUsername && !$showUserId && !$showEmail): ?>
	<img src="<?= $gravatarUrl ?>" alt="" class="img-fluid rounded rounded-3">
<?php else: ?>
	<div class="d-flex">
		<?php if ($showGravatar): ?>
			<div class="pe-2 pb-1">
				<img src="<?= $gravatarUrl ?>" alt="" class="img-fluid rounded rounded-3">
			</div>
		<?php endif; ?>

		<div>
			<?php if ($showUsername): ?><strong>
				<?php if ($showLink): ?>
					<a href="<?= $link ?>">
						<?= $name ?>
					</a>
				<?php else: ?>
					<?= $name ?>
				<?php endif; ?>
				</strong><?php endif; ?>
			<?php if ($showUserId): ?><small class="text-muted fst-italic">[ <?= $user_id ?> ]</small><?php endif; ?>
			<?php if (($showUsername || $showUserId) && ($showUsername || $showEmail)): ?><br /><?php endif; ?>
			<?php if ($showUsername): ?>
				<span class="text-success">
				<?= $username ?>
			</span>
				<?php if ($showEmail): ?><br /><?php endif; ?>
			<?php endif; ?>
			<?php if ($showEmail): ?>
				<span class="text-muted fst-italic fs-6">
				<?= $email ?>
			</span>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>



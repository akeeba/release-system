<?php
/**
 * PHP Exception Handler
 *
 * @copyright Copyright (c) 2018-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/** @var Throwable $e */
/** @var string $title */
/** @var bool $isPro */

$code = $e->getCode();
$code = !empty($code) ? $code : 500;

// 403 and 404 are re-thrown
if (in_array($code, [403, 404]))
{
	throw $e;
}

$app          = \Joomla\CMS\Factory::getApplication();
$user         = $app->getIdentity();
$isSuper      = !is_null($user) && $user->authorise('core.admin');
$isFrontend   = $app->isClient('site');
$hideTheError = $isFrontend && !(defined('JDEBUG') && (JDEBUG == 1)) && !$isSuper;
$isPro        = !isset($isPro) ? false : $isPro;

$app->setHeader('Status', $code);

if (!$isFrontend)
{
	\Joomla\CMS\Toolbar\ToolbarHelper::title($title . ' <small>Unhandled Exception</small>');
}
?>

<?php if ($hideTheError): ?>
	<div class="card">
		<h1 class="card-header bg-danger text-white">The application has stopped responding</h1>
		<div class="card-body">
			<p>
				Please contact the administrator of the site and let them know of this error and what you were doing when this
				happened.
			</p>
		</div>
	</div>
	<?php return true; endif; ?>
<div class="card my-3">
	<h1 class="card-header bg-danger text-white">
		<?= $title ?> - An unhandled Exception has been detected
	</h1>
	<div class="card-body">
		<h3>
			<span class="badge bg-danger"><?= htmlentities($code) ?></span>
			<?= htmlentities($e->getMessage()) ?>
		</h3>
		<p>
			File <code><?= htmlentities(str_ireplace(JPATH_ROOT, '&lt;root&gt;', $e->getFile())) ?></code>
			Line <span class="badge bg-info"><?= (int) $e->getLine() ?></span>
		</p>

		<?php if ($isPro): ?>
			<div class="alert alert-info">
				<p>
					<strong>Would you like us to help you faster?</strong>
				</p>
				<p>
					Save this page as PDF or HTML. Make a ZIP file containing this PDF or HTML file. When filing a support ticket please attach the ZIP file (<em>not</em> the PDF or HTML file itself).
				</p>
			</div>
			<p>
				<strong>Why do we need all that information?</strong>
				This information is an x-ray of your site at the time the error occurred. It lets us reproduce the issue or, if it's not a bug in our software, help you pinpoint the external reason which led to it.
			</p>
			<p>
				<strong>What about privacy?</strong>
				Attachments are private in our ticket system: only you and us can see them, <em>even if you file a public ticket</em>, and they are automatically deleted after a month.
			</p>
		<?php endif; ?>

		<hr />
		<p>
			<span class="icon icon-warning-2"></span>
			<em>
				The content below this point is for developers and power users.
			</em>
		</p>
		<hr />

		<p class="alert alert-warning">
			Joomla <?= JVERSION ?> – PHP <?= PHP_VERSION ?> on <?= PHP_OS ?>
		</p>

		<h3>Debug information</h3>
		<p>
			Exception type: <code><?= htmlentities(get_class($e)) ?></code>
		</p>
		<pre><?= htmlentities($e->getTraceAsString()) ?></pre>

		<?php while ($e = $e->getPrevious()): ?>
			<hr />
			<h4>Previous exception</h4>
			<strong>
				<span class="badge badge-danger"><?= htmlentities($code) ?></span>
				<?= htmlentities($e->getMessage()) ?>
			</strong>
			<p>
				File <code><?= htmlentities(str_ireplace(JPATH_ROOT, '&lt;root&gt;', $e->getFile())) ?></code> Line <span class="label label-info"><?= (int) $e->getLine() ?></span>
			</p>
			<p>
				Exception type: <code><?= htmlentities(get_class($e)) ?></code>
			</p>
			<pre><?= htmlentities($e->getTraceAsString()) ?></pre>
		<?php endwhile; ?>

		<h3>System information</h3>
		<table class="table table-striped">
			<tr>
				<td>Operating System (reported by PHP)</td>
				<td><?= PHP_OS ?></td>
			</tr>
			<tr>
				<td>PHP version (as reported <em>by your server</em>)</td>
				<td><?= PHP_VERSION ?></td>
			</tr>
			<tr>
				<td>PHP Built On</td>
				<td><?= htmlentities(php_uname()) ?></td>
			</tr>
			<tr>
				<td>PHP SAPI</td>
				<td><?= PHP_SAPI ?></td>
			</tr>
			<tr>
				<td>Server identity</td>
				<td><?= htmlentities($_SERVER['SERVER_SOFTWARE'] ?? getenv('SERVER_SOFTWARE') ?? '') ?></td>
			</tr>
			<tr>
				<td>Browser identity</td>
				<td><?= htmlentities($_SERVER['HTTP_USER_AGENT'] ?? '') ?></td>
			</tr>
			<tr>
				<td>Joomla! version</td>
				<td><?= JVERSION ?></td>
			</tr>
			<?php
			$db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
			if (!is_null($db)):
				?>
				<tr>
					<td>Database driver name</td>
					<td><?= $db->getName() ?></td>
				</tr>
				<tr>
					<td>Database driver type</td>
					<td><?= $db->getServerType() ?></td>
				</tr>
				<tr>
					<td>Database server version</td>
					<td><?= $db->getVersion() ?></td>
				</tr>
				<tr>
					<td>Database collation</td>
					<td><?= $db->getCollation() ?></td>
				</tr>
				<tr>
					<td>Database connection collation</td>
					<td><?= $db->getConnectionCollation() ?></td>
				</tr>
			<?php endif; ?>
			<tr>
				<td>PHP Memory limit</td>
				<td><?= function_exists('ini_get') ? htmlentities(ini_get('memory_limit')) : 'N/A' ?></td>
			</tr>
			<tr>
				<td>Peak Memory usage</td>
				<td><?= function_exists('memory_get_peak_usage') ? sprintf('%0.2fM', (memory_get_peak_usage() / 1024 / 1024)) : 'N/A' ?></td>
			</tr>
			<tr>
				<td>PHP Timeout (seconds)</td>
				<td><?= function_exists('ini_get') ? htmlentities(ini_get('max_execution_time')) : 'N/A' ?></td>
			</tr>
		</table>

		<h3>Request information</h3>
		<h4>$_GET</h4>
		<pre><?= htmlentities(print_r($_GET, true)) ?></pre>
		<h4>$_POST</h4>
		<pre><?= htmlentities(print_r($_POST, true)) ?></pre>
		<h4>$_COOKIE</h4>
		<pre><?= htmlentities(print_r($_COOKIE, true)) ?></pre>
		<h4>$_REQUEST</h4>
		<pre><?= htmlentities(print_r($_REQUEST, true)) ?></pre>

		<h3>Session state</h3>
		<pre><?= htmlentities(print_r($app->getSession()->all(), true)) ?></pre>

		<?php
		try
		{
			/** @var \Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory */
			$factory = $app->bootComponent('com_admin')->getMVCFactory();
			/** @var \Joomla\Component\Admin\Administrator\Model\SysinfoModel $model */
			$model = $factory->createModel('Sysinfo', 'Administrator');
		}
		catch (Exception $e)
		{
			return;
		}

		$directories = $model->getDirectory();

		try
		{
			$extensions = $model->getExtensions();
		}
		catch (Exception $e)
		{
			$extension = [];
		}

		$phpSettings = $model->getPhpSettings();
		$hasPHPInfo  = $model->phpinfoEnabled();
		?>

		<h3>PHP Settings</h3>
		<table class="table table-striped">
			<?php foreach ($phpSettings as $k => $v): ?>
				<tr>
					<td><?= $k ?></td>
					<td><?= htmlentities(print_r($v, true)) ?></td>
				</tr>
			<?php endforeach; ?>
		</table>

		<?php if ($hasPHPInfo):
			$phpInfo = $model->getPhpInfoArray(); ?>
			<h3>Loaded PHP Extensions</h3>
			<table class="table table-striped">
				<?php foreach ($phpInfo as $section => $data):
					if ($section == 'Core')
					{
						continue;
					} ?>
					<tr>
						<td><?= htmlentities($section) ?></td>
						<td>
							<?php if (in_array($section, ['curl', 'openssl', 'ssh2', 'ftp', 'session', 'tokenizer'])): ?>
								<pre><?= htmlentities(print_r($data, true)) ?></pre>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>

		<h3>Enabled Extensions</h3>
		<table class="table table-striped">
			<?php foreach ($extensions as $extension => $info):
				if (strtoupper($info['state']) != 'ENABLED')
				{
					continue;
				} ?>
				<tr>
					<td><?= htmlentities($extension) ?></td>
					<td><?= htmlentities($info['version']) ?></td>
					<td><?= htmlentities($info['type']) ?></td>
					<td><?= htmlentities($info['author']) ?></td>
					<td><?= htmlentities($info['authorUrl']) ?></td>
				</tr>
			<?php endforeach; ?>
		</table>

		<h3>Directory Status</h3>
		<table class="table table-striped">
			<?php foreach ($directories as $k => $v): ?>
				<tr>
					<td>
						<?= htmlentities($k) ?>
						<?= !empty($v['message']) ? "[{$v['message']}]" : '' ?>
					</td>
					<td>
						<?php if ($v['writable']): ?>
							<span class="label label-success">Writeable</span>
						<?php else: ?>
							<span class="label label-danger">Unwriteable</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>
</div>
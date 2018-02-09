<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var Throwable $e */
/** @var string $title */

$code         = $e->getCode();
$code         = !empty($code) ? $code : 500;
$app          = \JFactory::getApplication();
$isFrontend   = $app instanceof JApplicationSite;
$hideTheError = $isFrontend && !(defined('JDEBUG') && (JDEBUG == 1)) && !JFactory::getUser()->authorise('core.admin');

// 403 and 404 are re-thrown
if (in_array($code, [403, 404]))
{
	throw $e;
}

if (version_compare(JVERSION, '4', 'lt'))
{
	$app->setHeader('HTTP/1.1', $code);
}
else
{
	// In Joomla 4 we have to use the "Status" header, otherwise we get a fatal error saying that
	// HTTP/1.1 is not a valid header
	$app->setHeader('Status', $code);
}

if (!$isFrontend)
{
	JToolbarHelper::title($title . ' <small>Unhandled Exception</small>');
}

?>

<?php if ($hideTheError): ?>
	<h1>The application has stopped responding</h1>
	<p>
		Please contact the administrator of the site and let them know of this error and what you were doing when this happened.
	</p>
<?php return true; endif; ?>

<h1><?php echo $title ?> - An unhandled Exception has been detected</h1>
<h4>
	<span class="label label-danger"><?php echo $code ?></span> <?php echo $e->getMessage() ?>
</h4>
<p>
	File <code><?php echo str_ireplace(JPATH_ROOT, '&lt;root&gt;', $e->getFile()) ?></code>
	Line <span class="label label-info"><?php echo $e->getLine() ?></span>
</p>

<hr />
<p>
	<span class="icon icon-warning-2"></span>
	<em>
		The content below this point is for developers and power users.
	</em>
</p>
<hr/>

<h3>Debug information</h3>
<p>
	Exception type: <code><?php echo get_class($e) ?></code>
</p>
<pre><?php echo $e->getTraceAsString() ?></pre>

<h3>System information</h3>
<table class="table table-striped">
	<tr>
		<td>Operating System (reported by PHP)</td>
		<td><?php echo PHP_OS ?></td>
	</tr>
	<tr>
		<td>PHP version (as reported <em>by your server</em>)</td>
		<td><?php echo PHP_VERSION ?></td>
	</tr>
	<tr>
		<td>PHP Built On</td>
		<td><?php echo php_uname(); ?></td>
	</tr>
	<tr>
		<td>PHP SAPI</td>
		<td><?php echo PHP_SAPI ?></td>
	</tr>
	<tr>
		<td>Server identity</td>
		<td><?php echo isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : getenv('SERVER_SOFTWARE') ?></td>
	</tr>
	<tr>
		<td>Browser identity</td>
		<td><?php echo isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '' ?></td>
	</tr>
	<tr>
		<td>Joomla! version</td>
		<td><?php echo JVERSION ?></td>
	</tr>
	<tr>
		<td>Database driver name</td>
		<td><?php echo JFactory::getDbo()->getName() ?></td>
	</tr>
	<tr>
		<td>Database driver type</td>
		<td><?php echo JFactory::getDbo()->getServerType() ?></td>
	</tr>
	<tr>
		<td>Database server version</td>
		<td><?php echo JFactory::getDbo()->getVersion() ?></td>
	</tr>
	<tr>
		<td>Database collation</td>
		<td><?php echo JFactory::getDbo()->getCollation()?></td>
	</tr>
	<tr>
		<td>Database connection collation</td>
		<td><?php echo JFactory::getDbo()->getConnectionCollation()?></td>
	</tr>
	<tr>
		<td>PHP Memory limit</td>
		<td><?php echo function_exists('ini_get') ? ini_get('memory_limit') : 'N/A' ?></td>
	</tr>
	<tr>
		<td>Peak Memory usage</td>
		<td><?php echo function_exists('memory_get_peak_usage') ? sprintf('%0.2fM', (memory_get_peak_usage() / 1024 / 1024)) : 'N/A' ?></td>
	</tr>
	<tr>
		<td>PHP Timeout (seconds)</td>
		<td><?php echo function_exists('ini_get') ? ini_get('max_execution_time') : 'N/A' ?></td>
	</tr>
</table>

<h3>Request information</h3>
<h4>$_GET</h4>
<pre><?php print_r($_GET) ?></pre>
<h4>$_POST</h4>
<pre><?php print_r($_POST) ?></pre>
<h4>$_COOKIE</h4>
<pre><?php print_r($_COOKIE) ?></pre>
<h4>$_REQUEST</h4>
<pre><?php print_r($_REQUEST) ?></pre>

<h3>Session state</h3>
<pre><?php
	if (version_compare(JVERSION, '4', 'lt'))
	{
		print_r($app->getSession()->getData()->toArray());
	}
	else
	{
		print_r($app->getSession()->all());
	}
?></pre>

<?php
if (!include_once (JPATH_ADMINISTRATOR . '/components/com_admin/models/sysinfo.php')) return;

if (!class_exists('AdminModelSysInfo'))
{
    return;
}

try
{
	$model = new AdminModelSysInfo();
	$directories = $model->getDirectory();
	$extensions = $model->getExtensions();
	$phpSettings = $model->getPhpSettings();
	$hasPHPInfo = $model->phpinfoEnabled();
}
catch (\Exception $e)
{
    /**
     * If you are here, Joomla! had an unhandled exception inside its own code, typically decoding JSON. The only thing
     * you can do is die. If you try returning the unhandled exception will bubble up Joomla's error handler and you're
     * stuck with a misleading error. Sorry :(
     */
    die;
}
?>

<h3>PHP Settings</h3>
<table class="table table-striped">
	<?php foreach ($phpSettings as $k => $v): ?>
		<tr>
			<td><?php echo $k ?></td>
			<td><?php print_r($v) ?></td>
		</tr>
	<?php endforeach; ?>
</table>

<?php if($hasPHPInfo):
$phpInfo = $model->getPhpInfoArray(); ?>
<h3>Loaded PHP Extensions</h3>
<table class="table table-striped">
	<?php foreach($phpInfo as $section => $data):
	if ($section == 'Core') continue; ?>
	<tr>
		<td><?php echo $section ?></td>
		<td>
			<?php if (in_array($section, array('curl', 'openssl', 'ssh2', 'ftp', 'session', 'tokenizer'))): ?>
			<pre><?php print_r($data) ?></pre>
			<?php endif;?>
		</td>
	</tr>
	<?php endforeach; ?>
</table>
<?php endif; ?>

<h3>Enabled Extensions</h3>
<table class="table table-striped">
	<?php foreach ($extensions as $extension => $info):
	if (strtoupper($info['state']) != 'ENABLED') continue; ?>
	<tr>
		<td><?php echo $extension ?></td>
		<td><?php echo $info['version'] ?></td>
		<td><?php echo $info['type'] ?></td>
		<td><?php echo $info['author'] ?></td>
		<td><?php echo $info['authorUrl'] ?></td>
	</tr>
	<?php endforeach; ?>
</table>

<h3>Directory Status</h3>
<table class="table table-striped">
	<?php foreach ($directories as $k => $v): ?>
		<tr>
			<td>
				<?php echo $k ?>
				<?php echo !empty($v['message']) ? "[{$v['message']}]" : '' ?>
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

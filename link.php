<?php
// Internal linking script
$hardlink_files = array(
	# Live Update
	'../liveupdate/code/liveupdate.php'			=> 'component/backend/liveupdate/liveupdate.php',
);

$symlink_files = array(
	# Live Update
	'../liveupdate/code/LICENSE.txt'			=> 'component/backend/liveupdate/LICENSE.txt',
);

$symlink_folders = array(
	# Component translation
	'translations/component/backend/en-GB'		=> 'component/language/backend/en-GB',
	'translations/component/frontend/en-GB'		=> 'component/language/frontend/en-GB',
	# Live Update
	'../liveupdate/code/assets'					=> 'component/backend/liveupdate/assets',
	'../liveupdate/code/classes'				=> 'component/backend/liveupdate/classes',
	'../liveupdate/code/language'				=> 'component/backend/liveupdate/language',
	# FOF
	'../fof/fof'								=> 'component/fof',
);

define('IS_WINDOWS', substr(PHP_OS, 0, 3) == 'WIN');

function TranslateWinPath( $p_path )
{
	$is_unc = false;

	if (IS_WINDOWS)
	{
		// Is this a UNC path?
		$is_unc = (substr($p_path, 0, 2) == '\\\\') || (substr($p_path, 0, 2) == '//');
		// Change potential windows directory separator
		if ((strpos($p_path, '\\') > 0) || (substr($p_path, 0, 1) == '\\')){
			$p_path = strtr($p_path, '\\', '/');
		}
	}

	// Remove multiple slashes
	$p_path = str_replace('///','/',$p_path);
	$p_path = str_replace('//','/',$p_path);

	// Fix UNC paths
	if($is_unc)
	{
		$p_path = '//'.ltrim($p_path,'/');
	}

	return $p_path;
}

function doLink($from, $to, $type = 'symlink')
{
	$path = dirname(__FILE__);
	
	$realTo = $path .'/'. $to;
	$realFrom = $path.'/'.$from;
	if(IS_WINDOWS) {
		// Windows doesn't play nice with paths containing UNIX path separators
		$realTo = TranslateWinPath($realTo);
		$realFrom = TranslateWinPath($realFrom);
		// Windows doesn't play nice with relative paths in symlinks
		$realFrom = realpath($realFrom);
	}
	if(is_file($realTo) || is_dir($realTo) || is_link($realTo) || file_exists($realTo)) {
		if(IS_WINDOWS && is_dir($realTo)) {
			// Windows can't unlink() directory symlinks; it needs rmdir() to be used instead
			$res = @rmdir($realTo);
		} else {
			$res = @unlink($realTo);
		}
		if(!$res) {
			echo "FAILED UNLINK  : $realTo\n";
			return;
		}
	}
	if($type == 'symlink') {
		$res = @symlink($realFrom, $realTo);
	} elseif($type == 'link') {
		$res = @link($realFrom, $realTo);
	}
	if(!$res) {
		if($type == 'symlink') {
			echo "FAILED SYMLINK : $realTo\n";
		} elseif($type == 'link') {
			echo "FAILED LINK    : $realTo\n";
		}
	}
}


echo "Hard linking files...\n";
if(!empty($hardlink_files)) foreach($hardlink_files as $from => $to) {
	doLink($from, $to, 'link');
}

echo "Symlinking files...\n";
if(!empty($symlink_files)) foreach($symlink_files as $from => $to) {
	doLink($from, $to, 'symlink');
}

echo "Symlinking folders...\n";
if(!empty($symlink_folders)) foreach($symlink_folders as $from => $to) {
	doLink($from, $to, 'symlink');
}
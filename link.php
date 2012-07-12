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

$path = dirname(__FILE__);

if(!empty($hardlink_files)) foreach($hardlink_files as $from => $to) {
	if(is_file($path.'/'.$to)) {
		unlink($path.'/'.$to);
	}
	link($path.'/'.$from, $path.'/'.$to);
}

if(!empty($symlink_files)) foreach($symlink_files as $from => $to) {
	if(is_file($path.'/'.$to) || is_link($path.'/'.$to)) {
		unlink($path.'/'.$to);
	}
	symlink($path.'/'.$from, $path.'/'.$to);
}

if(!empty($symlink_folders)) foreach($symlink_folders as $from => $to) {
	if(is_dir($path.'/'.$to) || is_link($path.'/'.$to)) {
		unlink($path.'/'.$to);
	}
	symlink($path.'/'.$from, $path.'/'.$to);
}
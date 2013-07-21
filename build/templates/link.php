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
	# Build files
	'../buildfiles/bin'							=> 'build/bin',
	'../buildfiles/buildlang'					=> 'build/buildlang',
	'../buildfiles/phingext'					=> 'build/phingext',
	'../buildfiles/tools'						=> 'build/tools',

	# Component translation
	'translations/component/backend/en-GB'		=> 'component/language/backend/en-GB',
	'translations/component/frontend/en-GB'		=> 'component/language/frontend/en-GB',

	# Live Update
	'../liveupdate/code/assets'					=> 'component/backend/liveupdate/assets',
	'../liveupdate/code/classes'				=> 'component/backend/liveupdate/classes',
	'../liveupdate/code/language'				=> 'component/backend/liveupdate/language',

	# FOF
	'../fof/fof'								=> 'component/fof',

	# Akeeba Strapper
	'../fof/strapper'							=> 'component/strapper',
);
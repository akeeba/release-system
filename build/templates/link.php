<?php
// Internal linking script
$hardlink_files = array(
);

$symlink_files = array(
);

$symlink_folders = array(
	# Build files
	'../buildfiles/bin'							=> 'build/bin',
	'../buildfiles/buildlang'					=> 'build/buildlang',
	'../buildfiles/phingext'					=> 'build/phingext',
	'../buildfiles/tools'						=> 'build/tools',

	# Component translation
	'translations/component/backend/en-GB'		=> 'component/backend/language/en-GB',
	'translations/component/frontend/en-GB'		=> 'component/frontend/language/en-GB',

	# FOF
	'../fof/fof'								=> 'component/fof',

	# Akeeba Strapper
	'../fof/strapper'							=> 'component/strapper',
);
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
	'translations/component/backend/en-GB'		=> 'component/language/backend/en-GB',
	'translations/component/frontend/en-GB'		=> 'component/language/frontend/en-GB',

    // Usagestats
    '../usagestats/lib'                         => 'component/backend/assets/stats'
);
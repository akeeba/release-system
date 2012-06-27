<?php
function doTheHippyHippyShake($root, $target)
{
	foreach(new DirectoryIterator($root) as $oArea) {
		if(!$oArea->isDir()) continue;
		if($oArea->isDot()) continue;
		$area = $oArea->getFilename();

		$areaDir = $root.'/'.$area;

		foreach(new DirectoryIterator($areaDir) as $oModule)
		{
			if(!$oModule->isDir()) continue;
			if($oModule->isDot()) continue;
			$module = $oModule->getFilename();

			$moduleDir = $areaDir.'/'.$module;

			$from = $target.'/'.$area.'/'.$module.'/en-GB';
			$to = $moduleDir.'/language/en-GB';
			
			if(!is_dir($from)) {
				// Some things may be untranslated
				continue;
			}
			
			if(is_link($to)) {
				if(!@unlink($to)) {
					continue;
				}
			}
			@symlink($from, $to);
		}
	}	
}

$root = dirname(__FILE__).'/modules';
$target = dirname(__FILE__).'/translations/modules';
doTheHippyHippyShake($root, $target);

$root = dirname(__FILE__).'/plugins';
$target = dirname(__FILE__).'/translations/plugins';
doTheHippyHippyShake($root, $target);
<?php
function translateWinPath($p_path)
  {
    if (stristr(php_uname(), 'windows')) {
      // ----- Change potential windows directory separator
      if ((strpos($p_path, '\\') > 0) || (substr($p_path, 0,1) == '\\')) {
          $p_path = strtr($p_path, '\\', '/');
      }
      $p_path = strtr($p_path, '/','\\');
    }
    return $p_path;
  }

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
			
			if (stristr(php_uname(), 'windows') && is_dir($from)) {
				if(file_exists($to)) {
					if(!@unlink($to)) {
						continue;
					}
				}
			} elseif(is_link($to)) {
				if(!@unlink($to)) {
						continue;
				}
			}

			if (stristr(php_uname(), 'windows') && is_dir($from)) {
				$f = translateWinPath($from);
				$t = translateWinPath($to);
				$cmd = 'mklink /D "'.$to.'" "'.$from.'"';
				exec($cmd);
			} else {
				@symlink($from, $to);
			}
		}
	}	
}

$root = dirname(__FILE__).'/modules';
$target = dirname(__FILE__).'/translations/modules';
doTheHippyHippyShake($root, $target);

$root = dirname(__FILE__).'/plugins';
$target = dirname(__FILE__).'/translations/plugins';
doTheHippyHippyShake($root, $target);
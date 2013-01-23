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
	echo "$root\n";
	foreach(new DirectoryIterator($root) as $oArea) {
		if(!$oArea->isDir()) continue;
		if($oArea->isDot()) continue;
		$area = $oArea->getFilename();

		$areaDir = $root.'/'.$area;
		
		echo "\t$area\n";

		foreach(new DirectoryIterator($areaDir) as $oModule)
		{
			if(!$oModule->isDir()) continue;
			if($oModule->isDot()) continue;
			$module = $oModule->getFilename();
			
			echo "\t\t$module";

			$moduleDir = $areaDir.'/'.$module;

			$from = $target.'/'.$area.'/'.$module.'/en-GB';
			$to = $moduleDir.'/language/en-GB';
			
			if(!is_dir($from)) {
				// Some things may be untranslated
				echo "\tNot translated\n";
				continue;
			}
			
			if (stristr(php_uname(), 'windows') && is_dir($from)) {
				if(file_exists($to)) {
					if(!@unlink($to)) {
						echo "\tCannot remove old link\n";
						continue;
					}
				}
			} elseif(is_link($to)) {
				if(!@unlink($to)) {
					echo "\tCannot remove old link\n";
					continue;
				}
			} elseif(is_dir($to)) {
				// Let's do it The Hard Way™
				$cmd = 'rm -rf "'.$to.'"';
				exec($cmd);
				echo "\tHard Way™";
			}

			if (stristr(php_uname(), 'windows') && is_dir($from)) {
				$f = translateWinPath($from);
				$t = translateWinPath($to);
				$cmd = 'mklink /D "'.$to.'" "'.$from.'"';
				exec($cmd);
			} else {
				@symlink($from, $to);
			}
			
			echo "\tLINKED\n";
		}
	}	
}

$root = dirname(__FILE__).'/modules';
$target = dirname(__FILE__).'/translations/modules';
doTheHippyHippyShake($root, $target);

$root = dirname(__FILE__).'/plugins';
$target = dirname(__FILE__).'/translations/plugins';
doTheHippyHippyShake($root, $target);
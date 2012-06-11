<?php
/**
 * Automatically compares the new and old component repositories, figuring out
 * which files and folders have been removed from the old component's repo.
 */

$new = dirname(__FILE__).'/component';
$old = dirname(__FILE__).'/old-component';

function get_folders($root, $subdir = null)
{
	$folders = array();
	
	if(!empty($subdir)) {
		$path = $root.'/'.$subdir;
	} else {
		$path = $root;
	}
	
	$di = new DirectoryIterator($path);
	if(!empty($di)) foreach($di as $fileinfo) {
		if(!$fileinfo->isDir() || $fileinfo->isDot()) {
			continue;
		}
		$name = $fileinfo->getFilename();
		$newsubdir = empty($subdir) ? $name : $subdir.'/'.$name;
		
		$folders[] = $newsubdir;
		
		$subdirectories = get_folders($root, $newsubdir);
		if(!empty($subdirectories)) {
			foreach($subdirectories as $s) {
				$folders[] = $s;
			}
		}
	}
	
	return $folders;
}

function get_files($root)
{
	$files = array();
	
	$di = new DirectoryIterator($root);
	if(!empty($di)) foreach($di as $fileinfo) {
		$name = $fileinfo->getFilename();
		if($fileinfo->isFile()) {
			$files[] = $name;
		}
	}
	return $files;
}

function parent_directory_exists($dir, &$stack)
{
	$dirParts = explode('/', $dir);
	$countParts = count($dirParts);
	
	if($countParts == 1) return false;
	
	array_pop($dirParts);
	
	$newParts = array();
	while(count($dirParts)) {
		$newParts[] = array_shift($dirParts);
		$newDir = implode('/', $newParts);
		if(in_array($newDir, $stack)) return true;
	}
	return false;
}

function find_removed_files($newPath, $oldPath) {
	$ret = array();
	
	$newFiles = get_files($newPath);
	$oldFiles = get_files($oldPath);
	
	foreach($oldFiles as $of) {
		if(!in_array($of, $newFiles)) {
			$ret[] = $of;
		}
	}
	
	return $ret;
}

$new_folders = get_folders($new);
$old_folders = get_folders($old);

$removeFolders = array();
$removeFiles = array();
foreach($old_folders as $f) {
	if(parent_directory_exists($f, $removeFolders)) continue;
	
	if(!in_array($f, $new_folders)) {
		$removeFolders[] = $f;
	} else {
		$temp = find_removed_files($new.'/'.$f, $old.'/'.$f);
		if(count($temp)) foreach($temp as $fi) {
			$removeFiles[] = $f.'/'.$fi;
		}
	}
}

echo '$removeFolders = array('."\n";
foreach($removeFolders as $f) {
	echo "\t'$f',\n";
}
echo ");\n\n";

echo '$removeFiles = array('."\n";
foreach($removeFiles as $f) {
	echo "\t'$f',\n";
}
echo ");\n\n";
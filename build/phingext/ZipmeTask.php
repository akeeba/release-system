<?php

require_once "phing/Task.php";
require_once 'phing/tasks/system/MatchingTask.php';
include_once 'phing/util/SourceFileScanner.php';
include_once 'phing/mappers/MergeMapper.php';
include_once 'phing/util/StringHelper.php';
require_once 'pclzip.php';

/**
 * Creates a JPA archive
 * @author Nicholas K. Dionysopoulos
 * @version $Id: ZipmeTask.php 409 2011-01-24 09:30:22Z nikosdion $
 * @package akeebabuilder
 * @copyright Copyright (c)2009-2011 Nicholas K. Dionysopoulos
 * @license GNU GPL version 3 or, at your option, any later version
 */
class ZipmeTask extends MatchingTask {

    /**
     * @var PhingFile
     */
    private $zipFile;

    /**
     * @var PhingFile
     */
    private $baseDir;

    /**
     * File path prefix in zip archive
     *
     * @var string
     */
    private $prefix = null;

    /**
     * Whether to include empty dirs in the archive.
     */
    private $includeEmpty = true;

    private $filesets = array();
    private $fileSetFiles = array();

    /**
     * Add a new fileset.
     * @return FileSet
     */
    public function createFileSet() {
        $this->fileset = new ZipmeFileSet();
        $this->filesets[] = $this->fileset;
        return $this->fileset;
    }

    /**
     * Add a new fileset.
     * @return FileSet
     */
    public function createZipmeFileSet() {
        $this->fileset = new ZipmeFileSet();
        $this->filesets[] = $this->fileset;
        return $this->fileset;
    }

    /**
     * Set is the name/location of where to create the zip file.
     * @param PhingFile $destFile The output of the zip
     */
    public function setDestFile(PhingFile $destFile) {
        $this->zipFile = $destFile;
    }

    /**
     * Set the include empty dirs flag.
     * @param  boolean  Flag if empty dirs should be tarred too
     * @return void
     * @access public
     */
    public function setIncludeEmptyDirs($bool) {
        $this->includeEmpty = (boolean) $bool;
    }

    /**
     * This is the base directory to look in for things to zip.
     * @param PhingFile $baseDir
     */
    public function setBasedir(PhingFile $baseDir) {
        $this->baseDir = $baseDir;
    }

    /**
     * Sets the file path prefix for file in the zip file.
     *
     * @param string $prefix Prefix
     *
     * @return void
     */
    public function setPrefix($prefix) {
        $this->prefix = $prefix;
    }

    /**
     * do the work
     * @throws BuildException
     */
    public function main() {

        if ($this->zipFile === null) {
            throw new BuildException("zipFile attribute must be set!", $this->getLocation());
        }

        if ($this->zipFile->exists() && $this->zipFile->isDirectory()) {
            throw new BuildException("zipFile is a directory!", $this->getLocation());
        }

        if ($this->zipFile->exists() && !$this->zipFile->canWrite()) {
            throw new BuildException("Can not write to the specified zipFile!", $this->getLocation());
        }

        // shouldn't need to clone, since the entries in filesets
        // themselves won't be modified -- only elements will be added
        $savedFileSets = $this->filesets;

        try {
            if (empty($this->filesets)) {
                throw new BuildException("You must supply some nested filesets.",
                                         $this->getLocation());
            }

            $this->log("Building ZIP: " . $this->zipFile->__toString(), Project::MSG_INFO);

            $zip = new PclZip( $this->zipFile->getAbsolutePath() );

            if ($zip->errorCode() != 1)
            {
                throw new Exception("PclZip::open() failed: " . $zip->errorInfo() );
            }

            foreach($this->filesets as $fs) {

                $files = $fs->getFiles($this->project, $this->includeEmpty);

                $fsBasedir = (null != $this->baseDir) ? $this->baseDir :
                                    $fs->getDir($this->project);

                $filesToZip = array();
                for ($i=0, $fcount=count($files); $i < $fcount; $i++) {
                    $f = new PhingFile($fsBasedir, $files[$i]);

                    //$filesToZip[] = realpath($f->getPath());
					$fileAbsolutePath = $f->getPath();
					$fileDir = rtrim(dirname($fileAbsolutePath), '/\\');
					$fileBase = basename($fileAbsolutePath);

					if(substr($fileDir, -4) == '.svn') continue;
					if($fileBase == '.svn') continue;
					if(substr( rtrim($fileAbsolutePath,'/\\'), -4 ) == '.svn' ) continue;
					if($fileBase == '.gitignore') continue;
					if(strtolower($fileBase) == '.ds_store') continue;
					if($fileBase == 'Thumbs.db') continue;

					//echo "\t\t$fileAbsolutePath\n";

                    $filesToZip[] = $f->getPath();
                }

                /*
                $zip->add($filesToZip,
                	PCLZIP_OPT_ADD_PATH, is_null($this->prefix) ? '' : $this->prefix ,
                	PCLZIP_OPT_REMOVE_PATH, realpath($fsBasedir->getPath()) );
                */
                $zip->add($filesToZip,
                	PCLZIP_OPT_ADD_PATH, is_null($this->prefix) ? '' : $this->prefix ,
                	PCLZIP_OPT_REMOVE_PATH, $fsBasedir->getPath() );
                
            }

        } catch (IOException $ioe) {
                $msg = "Problem creating ZIP: " . $ioe->getMessage();
                $this->filesets = $savedFileSets;
                throw new BuildException($msg, $ioe, $this->getLocation());
        }

        $this->filesets = $savedFileSets;
    }
}

/**
 * This is a FileSet with the to specify permissions.
 *
 * Permissions are currently not implemented by PEAR Archive_Tar,
 * but hopefully they will be in the future.
 *
 */
class ZipmeFileSet extends FileSet {

    private $files = null;

    /**
     *  Get a list of files and directories specified in the fileset.
     *  @return array a list of file and directory names, relative to
     *    the baseDir for the project.
     */
    public function getFiles(Project $p, $includeEmpty = true) {

        if ($this->files === null) {

            $ds = $this->getDirectoryScanner($p);
            $this->files = $ds->getIncludedFiles();

            if ($includeEmpty) {

                // first any empty directories that will not be implicitly added by any of the files
                $implicitDirs = array();
                foreach($this->files as $file) {
                    $implicitDirs[] = dirname($file);
                }

                $incDirs = $ds->getIncludedDirectories();

                // we'll need to add to that list of implicit dirs any directories
                // that contain other *directories* (and not files), since otherwise
                // we get duplicate directories in the resulting tar
                foreach($incDirs as $dir) {
                    foreach($incDirs as $dircheck) {
                        if (!empty($dir) && $dir == dirname($dircheck)) {
                            $implicitDirs[] = $dir;
                        }
                    }
                }

                $implicitDirs = array_unique($implicitDirs);

                // Now add any empty dirs (dirs not covered by the implicit dirs)
                // to the files array.

                foreach($incDirs as $dir) { // we cannot simply use array_diff() since we want to disregard empty/. dirs
                    if ($dir != "" && $dir != "." && !in_array($dir, $implicitDirs)) {
                        // it's an empty dir, so we'll add it.
                        $this->files[] = $dir;
                    }
                }
            } // if $includeEmpty

        } // if ($this->files===null)

        return $this->files;
    }

}

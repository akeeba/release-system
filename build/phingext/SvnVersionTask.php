<?php
require_once 'phing/Task.php';
require_once 'phing/tasks/ext/svn/SvnBaseTask.php';

/**
 * SVN latest tree version to Phing property
 * @version $Id: SvnVersionTask.php 147 2010-05-26 09:15:42Z nikosdion $
 * @package akeebabuilder
 * @copyright Copyright (c)2009-2010 Nicholas K. Dionysopoulos
 * @license GNU GPL version 3 or, at your option, any later version
 * @author nicholas
 */
class SvnVersionTask extends SvnBaseTask
{
    private $propertyName = "svn.version";

    /**
     * Sets the name of the property to use
     */
    function setPropertyName($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * Returns the name of the property to use
     */
    function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * Sets the path to the working copy
     */
    function setWorkingCopy($wc)
    {
        $this->workingCopy = $wc;
    }
    
    /**
     * The main entry point
     *
     * @throws BuildException
     */
    function main()
    {
		$this->setup('info');
		

		exec('svnversion '.escapeshellarg($this->workingCopy), $out);
		if( strpos($out[0],':') === false )
		{
			$version = intval($out[0]);
		}
		else
		{
			$parts = explode(':', $out[0]);
			$version = intval($parts[1]);
		}
		
		if( $version > 0 )
		{
			$this->project->setProperty($this->getPropertyName(), $version);
		}
		else
		{
			throw new BuildException("Failed to parse the output of 'svnversion'.");
		}            
    }
}
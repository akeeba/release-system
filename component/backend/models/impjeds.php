<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelImpjeds extends F0FModel
{
	/**
	 * Returns a list of packages in a JoomlaCode FRS repository
	 * @param string $project The JoomlaCode FRS project name, e.g. 'joomla'
	 * @return array The package names
	 */
	function getPackages($project)
	{
		$frs = new ArsModelFRS();
		$frs->setProject($project);
		return array_keys($frs->getPackages());
	}

	/**
	 * Returns a list of the releases in a JoomlaCode FRS package
	 * @param string $project The JoomlaCode FRS project name, e.g. 'joomla'
	 * @param string $packageName The package inside the project, e.g. 'Joomla1.5'
	 * @return array The release names
	 */
	function getReleases($project, $packageName)
	{
		$frs = new ArsModelFRS();
		$frs->setProject($project);
		return array_keys($frs->getReleases($packageName));
	}

	/**
	 * Returns a list of the releases in a JoomlaCode FRS package
	 * @param string $project The JoomlaCode FRS project name, e.g. 'joomla'
	 * @param string $packageName The package inside the project, e.g. 'Joomla1.5'
	 * @param string $releaseName The name of the release inside the package, e.g. 'Joomla1.5.20'
	 * @return array The file names (keys) and URLs (values)
	 */
	function getFiles($project, $packageName, $releaseName)
	{
		$frs = new ArsModelFRS();
		$frs->setProject($project);
		return $frs->getFiles($packageName, $releaseName);
	}

	/**
	 * Return a list of ARS categories
	 * @return array
	 */
	function getArsCategories()
	{
		return F0FModel::getTmpInstance('Categories','ArsModel')->getItemList(true);
	}

	/**
	 * Return a list of ARS releases inside a category
	 * @param int $catid The category ID
	 * @return array
	 */
	function getArsReleases($catid)
	{
		return F0FModel::getTmpInstance('Releases','ArsModel')
			->published('')
			->category($catid)
			->getItemList(true);
	}

	/**
	 * Create a new release
	 * @param $catid int The category ID
	 * @param $releaseName string The release version number
	 * @return int The integer ID of the new or exisiting release
	 */
	function createArsRelease($catid, $releaseName)
	{
		// Try to find an existing release by the same name (version)
		$existing = F0FModel::getTmpInstance('Releases','ArsModel')
			->published('')
			->category($catid)
			->version($releaseName)
			->getItemList(true);
		if(!empty($existing))
		{
			// Match found, return it
			$item = array_shift($existing);
			return $item->id;
		}
		else
		{
			// No match found, create a new release in this category
			$data = array(
				'id'			=> 0,
				'version'		=> $releaseName,
				'category_id'	=> $catid,
				'maturity'		=> 'alpha'
			);
			$model = F0FModel::getTmpInstance('Releases','ArsModel');
			$model->save($data);
			$newItem = $model->getSavedTable();
			return $newItem->id;
		}
	}

	/**
	 * Create a new file inside the release
	 * @param $releaseId int The release's numeric ID
	 * @param $remoteName string The remote file's URL
	 *
	 * @return int The ID of the created or exisiting file record
	 */
	function createArsFile($releaseId, $remoteName)
	{
		// Look for an existing item
		$existing = F0FModel::getTmpInstance('Items','ArsModel')
			->published('')
			->release($releaseId)
			->url($remoteName)
			->getItemList(true);
		if(!empty($existing))
		{
			// Match found, return it
			$item = array_shift($existing);
			return $item->id;
		}
		else
		{
			// No match found, create a new release in this category
			$data = array(
				'release_id'	=> $releaseId,
				'type'			=> 'link',
				'url'			=> $remoteName
			);
			$model = F0FModel::getTmpInstance('Items','ArsModel');
			$status = $model->save($data);
			if(!$status) return $model->getError();
			$newItem = $model->getSavedTable();
			return $newItem->id;
		}
	}
}

/**
 * A simple model to handle interaction with JoomlaCode's FRS
 */
class ArsModelFRS
{
	private $project = '';
	private $packages = array();
	private $releases = array();
	private $files = array();

	public $useCache = false;

	public function setProject($project)
	{
		$this->project = $project;
	}

	public function getProject()
	{
		return $this->project;
	}

	private function getCacheFilename($url)
	{
		return JPATH_CACHE.'/ars_'.md5($url).'.cache';
	}

	private function cacheExists($url)
	{
		JLoader::import('joomla.filesystem.file');
		return JFile::exists( $this->getCacheFilename($url) );
	}

	private function readCache($url)
	{
		JLoader::import('joomla.filesystem.file');
		return JFile::read( $this->getCacheFilename($url) );
	}

	private function writeCache($url, $data)
	{
		JLoader::import('joomla.filesystem.file');
		return JFile::write( $this->getCacheFilename($url), $data );
	}

	private function getPageContent($url)
	{
		if($this->useCache)
		{
			if($this->cacheExists($url))
			{
				return $this->readCache($url);
			}
		}

		if(function_exists('curl_exec'))
		{
			// Use cURL
			$curl_options = array(
				CURLOPT_AUTOREFERER		=> true,
				CURLOPT_FAILONERROR		=> true,
				CURLOPT_FOLLOWLOCATION	=> true,
				CURLOPT_HEADER			=> false,
				CURLOPT_RETURNTRANSFER	=> true,
				CURLOPT_SSL_VERIFYPEER	=> false,
				CURLOPT_CONNECTTIMEOUT	=> 5,
				CURLOPT_MAXREDIRS		=> 20
			);
			$ch = curl_init($url);
			foreach($curl_options as $option => $value)
			{
				@curl_setopt($ch, $option, $value);
			}
			$data = curl_exec($ch);
		}
		elseif( ini_get('allow_url_fopen') )
		{
			// Use fopen() wrappers
			$options = array( 'http' => array(
				'max_redirects' => 10,          // stop after 10 redirects
				'timeout'       => 20         // timeout on response
			) );
			$context = stream_context_create( $options );
			$data = @file_get_contents( $url, false, $context );
		}
		else
		{
			$data = false;
		}

		if($this->useCache)
		{
			if($data !== false)
			{
				$this->writeCache($url, $data);
			}
		}

		return $data;
	}

	private function getMorePages($data)
	{
		$pages = array();
		$regex = '#<a href="(.*_br_pkgrls_page=[0-9]*)">(.*)</a>#iU';
		preg_match_all($regex, $data, $m);
		if(!empty($m[0]))
		{
			$count = count($m[1]);
			for($i = 0; $i < $count; $i++)
			{
				if(!is_numeric($m[2][$i])) continue;
				$pages[ $m[2][$i] ] = $m[1][$i];
			}
		}
		return $pages;
	}

	public function getPackages()
	{
		if(empty($this->packages))
		{
			$url = 'http://joomlacode.org/gf/project/'.urlencode($this->project).'/frs';
			$data = $this->getPageContent($url);
			$pages = $this->getMorePages($data);

			while(!empty($pages))
			{
				$url = 'http://joomlacode.org'.html_entity_decode(array_pop($pages));
				$data .= $this->getPageContent($url);
			}

			// Parse packages
			$this->packages = array();
			$regex = '#<a href="(/gf/project/[\w]*/frs/\?action=FrsReleaseBrowse\&amp\;frs_package_id=[0-9]*)">.*&nbsp;(.*)</a>#iU';
			preg_match_all($regex, $data, $m);

			if(!empty($m))
			{
				$count = count($m[0]);
				for($i = 0; $i < $count; $i++)
				{
					$this->packages[ $m[2][$i] ] = $m[1][$i];
				}
			}

			ksort($this->packages);
		}

		return $this->packages;
	}

	public function getReleases($packageName)
	{
		if(!isset($this->releases[$packageName]))
		{
			$this->releases[$packageName] = array();

			$packages = $this->getPackages();
			if(isset($packages[ $packageName ]))
			{
				$url = 'http://joomlacode.org'.html_entity_decode($packages[ $packageName ]);
				$data = $this->getPageContent($url);
				$pages = $this->getMorePages($data);

				while(!empty($pages))
				{
					$url = 'http://joomlacode.org'.html_entity_decode(array_pop($pages));
					$data .= $this->getPageContent($url);
				}

				$regex = '#<a href="(/gf/project/[\w]*/frs/\?action=FrsReleaseView\&amp\;release_id=[0-9]*)">(.*)</a>#iU';
				preg_match_all($regex, $data, $m);
				if(!empty($m))
				{
					$count = count($m[0]);
					for($i = 0; $i < $count; $i++)
					{
						$this->releases[$packageName][ $m[2][$i] ] = $m[1][$i];
					}
				}
			}

			ksort($this->releases[$packageName]);
		}

		return $this->releases[$packageName];
	}

	public function getFiles($packageName, $releaseName)
	{
		if(!isset($this->files[$packageName][$releaseName]))
		{
			$this->files[$packageName][$releaseName] = array();
			$releases = $this->getReleases($packageName);
			if(isset($releases[$releaseName]))
			{
				$url = 'http://joomlacode.org'.html_entity_decode($releases[$releaseName]);
				$data = $this->getPageContent($url);
				$pages = $this->getMorePages($data);

				while(!empty($pages))
				{
					$url = 'http://joomlacode.org'.html_entity_decode(array_pop($pages));
					$data .= $this->getPageContent($url);
				}

				$regex = '#<a href="(/gf/download/frsrelease/[0-9]*/[0-9]*/.*)">(.*)</a>#iU';
				preg_match_all($regex, $data, $m);
				if(!empty($m))
				{
					$count = count($m[0]);
					for($i = 0; $i < $count; $i++)
					{
						$filename = 'http://joomlacode.org'.$m[1][$i];
						$this->files[$packageName][$releaseName][ $m[2][$i] ] = $filename;
					}
				}
			}

			ksort($this->files[$packageName][$releaseName]);
		}

		return $this->files[$packageName][$releaseName];
	}
}
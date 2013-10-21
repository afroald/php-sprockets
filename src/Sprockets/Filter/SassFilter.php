<?php namespace Sprockets\Filter;

use Sprockets\Asset;
use Sprockets\TmpFile;

class SassFilter extends BaseProcessFilter {

	protected $scss = false;
	protected $compass = false;
	protected $cachePath;
	protected $loadPaths = array();

	public function __construct($config = array())
	{
		if (array_key_exists('compass', $config))
		{
			$this->setCompass($config['compass']);
		}

		if (array_key_exists('cache_path', $config))
		{
			$this->setCachePath($config['cache_path']);
		}
	}

	public function setCachePath($path)
	{
		$this->cachePath = $path;
	}

	public function addLoadPath($path)
	{
		$this->loadPaths[] = $path;
	}

	public function setCompass($enabled = true)
	{
		$this->compass = $enabled;
	}

	protected function command(Asset $asset, TmpFile $tmpFile)
	{
		$this->addLoadPath($asset->path);

		$options = array();

		if ($this->compass)
		{
			$options[] = '--compass';
		}

		if ($this->scss)
		{
			$options[] = '--scss';
		}

		if ($this->cachePath)
		{
			$options[] = '--cache-location';
			$options[] = $this->cachePath;
		}
		else {
			$options[] = '--no-cache';
		}

		foreach ($this->loadPaths as $loadPath)
		{
			$options[] = '--load-path';
			$options[] = $loadPath;
		}

		return array_merge(array('sass'), $options, array($tmpFile->getRealPath()));
	}

}
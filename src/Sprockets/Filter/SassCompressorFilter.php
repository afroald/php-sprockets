<?php namespace Sprockets\Filter;

use Sprockets\Asset;
use Sprockets\TmpFile;

class SassCompressorFilter extends BaseProcessFilter {

	protected $cachePath;

	public function __construct($config = array())
	{
		if (array_key_exists('cache_path', $config))
		{
			$this->setCachePath($config['cache_path']);
		}
	}

	public function setCachePath($path)
	{
		$this->cachePath = $path;
	}

	protected function command(Asset $asset, TmpFile $tmpFile)
	{

		$options = array(
			'--scss',
			'--style',
			'compressed'
		);

		if ($this->cachePath)
		{
			$options[] = '--cache-location';
			$options[] = $this->cachePath;
		}
		else {
			$options[] = '--no-cache';
		}

		return array_merge(array('sass'), $options, array($tmpFile->getRealPath()));
	}

}
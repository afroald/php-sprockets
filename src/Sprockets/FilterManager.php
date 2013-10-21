<?php namespace Sprockets;

class FilterManager {

	protected $engines            = array();
	protected $engineCreators     = array();
	protected $compressors        = array();
	protected $compressorCreators = array();

	protected function createEngine($extension)
	{
		$creator = $this->engineCreators[$extension];
		$engine = is_callable($creator) ? $creator() : $creator;

		if ($engine)
		{
			$this->engines[$extension] = $engine;
		}
	}

	public function registerEngine($extension, $creator)
	{
		$this->engineCreators[$extension] = $creator;
	}

	public function hasEngine($extension)
	{
		return array_key_exists($extension, $this->engineCreators);
	}

	public function engine($extension)
	{
		if (!$this->hasEngine($extension))
		{
			return null;
		}

		if (!array_key_exists($extension, $this->engines))
		{
			$this->createEngine($extension);
		}

		return $this->engines[$extension];
	}

	protected function createCompressor($mimeType)
	{
		$creator = $this->engineCreators[$mimeType];

		$compressor = is_callable($creator) ? $creator() : $creator;

		if ($compressor)
		{
			$this->compressors[$mimeType] = $compressor;
		}
	}

	public function registerCompressor($mimeType, $creator)
	{
		$this->compressorCreators[$mimeType] = $creator;
	}

	public function hasCompressor($mimeType)
	{
		return array_key_exists($mimeType, $this->compressorCreators);
	}

	public function compressor($mimeType)
	{
		if (!$this->hasCompressor($mimeType))
		{
			return null;
		}

		if (!array_key_exists($mimeType, $this->compressors))
		{
			$this->createCompressor($mimeType);
		}

		return $this->compressors[$mimeType];
	}

}
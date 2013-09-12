<?php namespace Sprockets;

use Sprockets\DirectiveProcessor;
use Sprockets\Finder;
use Sprockets\Engine\CoffeeScriptEngine;
use Sprockets\Engine\ScssEngine;

class Pipeline {
	public $finder;

	public $loadPaths = array();

	protected $mimeTypes = array(
		'.css' => 'text/css',
		'.js' => 'application/javascript',

		'.eot' => 'application/vnd.ms-fontobject',
		'.ttf' => 'application/octet-stream',
		'.woff' => 'application/font-woff'
	);

	protected $preProcessors = array();
	protected $postProcessors = array();
	protected $engines = array();
	protected $compressors = array();

	public function __construct(array $loadPaths)
	{
		$this->loadPaths = $loadPaths;

		$this->finder = new Finder($loadPaths);

		$this->registerEngine('.coffee', new CoffeeScriptEngine($this));
		$this->registerEngine('.scss', new ScssEngine($this));
	}

	public function asset($name, $type = null)
	{
		$file = $this->finder->find($name, $type);

		$asset =  new Asset($this, $file);

		return $asset;
	}

	public function mimeTypes()
	{
		return $this->mimeTypes;
	}

	public function engine($extension)
	{
		return array_key_exists($extension, $this->engines) ? $this->engines[$extension] : null;
	}

	public function engines()
	{
		return $this->engines;
	}

	public function registerMimeType($mimeType, $extension)
	{
		$this->mimeTypes[$extension] = $mimeType;
	}

	public function registerPreProcessor($mimeType, $class)
	{
		if (!array_key_exists($mimeType, $this->preProcessors) or !is_array($this->preProcessors[$mimeType]))
		{
			$this->preProcessors[$mimeType] = array();
		}

		$this->preProcessors[$mimeType][] = $class;
	}

	public function registerPostProcessor($mimeType, $class)
	{

	}

	public function registerEngine($extension, $class)
	{
		$this->engines[$extension] = $class;
	}

	public function registerCompressor($mimeType, $id, $class)
	{
		if (!array_key_exists($mimeType, $this->compressors) or !is_array($this->compressors[$mimeType]))
		{
			$this->compressors[$mimeType] = array();
		}

		$this->compressors[$mimeType][$id] = $class;
	}
}
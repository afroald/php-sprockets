<?php namespace Sprockets;

use Sprockets\Engine\CoffeeScriptEngine;
use Sprockets\Engine\ScssEngine;
use Sprockets\Engine\LessEngine;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

class Pipeline {
	public $finder;

	public $loadPaths = array();

	protected $mimeTypes = array(
		'css' => 'text/css',
		'js' => 'application/javascript',

		'eot' => 'application/vnd.ms-fontobject',
		'ttf' => 'application/octet-stream',
		'woff' => 'application/font-woff'
	);

	protected $preProcessors = array();
	protected $postProcessors = array();
	protected $engines = array();
	protected $compressors = array();

	protected $assets = array();

	public function __construct(array $loadPaths)
	{
		$this->loadPaths = $loadPaths;

		$this->finder = new Finder($loadPaths);

		$this->registerEngine('coffee', new CoffeeScriptEngine($this));
		$this->registerEngine('scss', new ScssEngine($this));
		$this->registerEngine('less', new LessEngine($this));
	}

	public function asset($logicalPath, $type = null)
	{
		$file = $this->finder->find($logicalPath, $type);

		if (!is_a($file, 'SplFileInfo'))
		{
			throw new AssetNotFoundException($logicalPath);
		}

		if (array_key_exists($file->getRelativePathname(), $this->assets))
		{
			return $this->assets[$file->getRelativePathname()];
		}

		$asset = new Asset($this, $file->getPathname(), $file->getRelativePathname());

		$this->assets[$file->getRelativePath()] = $asset;

		return $asset;
	}

	public function all()
	{
		$pipeline = $this;

		$files = array_map(function($file) use($pipeline) {
			return new Asset($pipeline, $file->getPathname(), $file->getRelativePathname());
		}, $this->finder->all());

		return new Collection($files);
	}

	public function guessMimeType(Asset $asset)
	{
		$extensions = $asset->extensions();

		if (array_key_exists($extensions[0], $this->mimeTypes))
		{
			return $this->mimeTypes[$extensions[0]];
		}

		$guesser = MimeTypeGuesser::getInstance();

		return $guesser->guess($asset->pathname());
	}

	public function canProcess(Asset $asset)
	{
		return count(array_intersect(array_keys($this->engines), $asset->extensions())) > 0;
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
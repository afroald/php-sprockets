<?php namespace Sprockets;

use Sprockets\Filter;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

class Pipeline {
	public $finder;
	public $filters;
	public $assetCache;

	public $loadPaths = array();

	protected $mimeTypes = array(
		'css' => 'text/css',
		'js' => 'application/javascript',

		'ttf' => 'application/x-font-ttf',
		'otf' => 'application/font-otf',
		'eot' => 'application/octet-stream',
		'woff' => 'application/font-woff'
	);

	protected $assets = array();

	public function __construct(array $loadPaths, $cachePath = null)
	{
		$this->loadPaths = $loadPaths;

		$this->finder = new Finder($loadPaths);
		$this->assetCache = new Asset\Cache($cachePath);
		$this->filters = new FilterManager;

		$this->registerDefaultFilters();
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

		if ($this->assetCache->isValid())
		{
			$asset = new CachedAsset($this, $file->getPathname(), $file->getRelativePathname(), $this->assetCache);
		}
		else
		{
			$asset = new Asset($this, $file->getPathname(), $file->getRelativePathname());
		}

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
		if (count(array_intersect(array('js', 'css'), $asset->extensions())) > 0)
		{
			return true;
		}

		$filters = $this->filters;

		$assetFilters = array_filter($asset->extensions(), function($extension) use ($filters)
		{
			return $filters->hasEngine($extension) || $filters->hasCompressor($extension);
		});

		return count($assetFilters) > 0;
	}

	public function mimeTypes()
	{
		return $this->mimeTypes;
	}

	public function registerMimeType($mimeType, $extension)
	{
		$this->mimeTypes[$extension] = $mimeType;
	}

	protected function registerDefaultFilters()
	{
		$this->filters->registerEngine('coffee', new Filter\CoffeeScriptFilter());
		$this->filters->registerEngine('scss', new Filter\ScssFilter());
		$this->filters->registerEngine('less', new Filter\LessFilter());
	}
}
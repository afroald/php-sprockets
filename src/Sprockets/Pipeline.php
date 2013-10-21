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

	protected $config = array(
		'debug' => false,
		'cache_path' => null,

		'js_compressor' => null,
		'css_compressor' => null,

		'filters' => array(
			'coffeescript' => array(
				'bare' => false
			),
			'sass' => array(
				'compass' => false
			)
		)
	);

	public function __construct(array $loadPaths, $config = array())
	{
		$this->loadPaths = $loadPaths;
		$this->config = $this->mergeOptions($this->config, $config);

		$this->finder = new Finder($loadPaths);
		$this->assetCache = new Asset\Cache($this->config['cache_path']);
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
		$this->filters->registerEngine('coffee', new Filter\CoffeeScriptFilter($this->config['filters']['coffeescript']));
		$this->filters->registerEngine('less', new Filter\LessFilter());

		if ($this->assetCache->isValid())
		{
			$this->config['filters']['sass']['cache_path'] = $this->config['cache_path'] . DIRECTORY_SEPARATOR . 'sass-cache';
		}
		$this->filters->registerEngine('sass', new Filter\SassFilter($this->config['filters']['sass']));
		$this->filters->registerEngine('scss', new Filter\ScssFilter($this->config['filters']['sass']));
	}

	protected function mergeOptions($defaults, $options)
	{
		foreach ($options as $key => $value)
		{
			if (array_key_exists($key, $defaults))
			{
				if (is_array($defaults[$key]))
				{
					$defaults[$key] = $this->mergeOptions($defaults[$key], $options[$key]);
				}
				else
				{
					$defaults[$key] = $value;
				}
			}
			else
			{
				$defaults[$key] = $value;
			}
		}

		return $defaults;
	}
}
<?php namespace Sprockets;

use SplFileInfo;
use Sprockets\Exception\AssetNotFoundException;

class Asset {
	protected $static = false;
	protected $processedContent = '';
	protected $bundledContent = '';

	protected $pipeline;

	protected $source;

	protected $directiveProcessor;

	public function __construct(Pipeline $pipeline, $path, $logicalPath)
	{
		$this->pipeline = $pipeline;
		$this->path = new SplFileInfo($path);
		$this->logicalPath = new SplFileInfo($logicalPath);

		if (!$this->path->isFile())
		{
			throw new AssetNotFoundException($logicalPath);
		}

		$this->mimeType = $this->pipeline->guessMimeType($this);

		// Check if this is a file we can process. If not, treat it as a static asset (image, font, etc.)
		if (!$this->pipeline->canProcess($this))
		{
			$this->static = true;
		}
	}

	/**
	 * Allow the functions on this object to be accessed as properties.
	 * @param  string $property
	 * @return mixed
	 */
	public function __get($property) {
		if (method_exists($this, $property))
		{
			return $this->$property();
		}

		$trace = debug_backtrace();
		trigger_error(
			'Undefined property via __get(): ' . $property .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_ERROR);
		return null;
	}

	/**
	 * Return the processed and bundled content.
	 * @return string
	 */
	public function content()
	{
		if ($this->static)
		{
			return $this->source->get();
		}

		$this->process();
		$this->bundle();

		return $this->bundledContent;
	}

	/**
	 * Return the processed content when this object is typecasted to a string.
	 * @return string
	 */
	public function __toString()
	{
		return $this->content();
	}

	public function logicalPath()
	{
		return $this->logicalPath->getPath();
	}

	public function logicalPathname()
	{
		return $this->logicalPath->getPathname();
	}

	public function path()
	{
		return $this->path->getPath();
	}

	public function pathname()
	{
		return $this->path->getPathname();
	}

	/**
	 * Return the processed content excluding the dependencies.
	 * @return string
	 */
	public function body()
	{
		if ($this->static)
		{
			return $this->content();
		}

		$this->process();

		return $this->processedContent;
	}

	/**
	 * Return the filename as it would be after processing
	 * @return string
	 */
	public function name($digest = false)
	{
		$extensions = $this->extensions();
		$basename = $this->source->getBasename(implode($extensions, ''));

		$filename = $basename;
		if ($digest) {
			$filename.= '-' . $this->digest();
		}
		$filename.= $extensions[0];

		return $filename;
	}

	public function extensions()
	{
		$matches = array();

		preg_match_all('/\.([^.]+)/', $this->path->getBasename(), $matches);

		return $matches[1];
	}

	public function mimeType()
	{
		return $this->mimeType;
	}

	/**
	 * Return an array of assets that this asset depends on.
	 * @return array
	 */
	public function dependencies()
	{
		return $this->static ? array($this) : $this->directiveProcessor()->dependencies();
	}

	/**
	 * Return an array of assets that this asset requires.
	 * @return array
	 */
	public function requiredAssets()
	{
		return $this->static ? array($this) : $this->directiveProcessor()->requiredAssets();
	}

	/**
	 * Return the time this asset or one of its depentencies was modified.
	 * @return DateTime
	 */
	public function lastModified()
	{
		$lastModified = new \DateTime();
		$lastModified->setTimestamp($this->source->getMTime());

		// Don't try to fetch dependencies on a static asset. It gives segmentation faults :-s
		if ($this->static) return $lastModified;

		$dependencies = $this->dependencies;

		// Collect last modified times for all dependencies
		$currentAsset = $this;
		$mtimes = array_map(function($dependency) use($currentAsset)
		{
			if ($dependency == $currentAsset)
			{
				return $lastModified;
			}

			return $dependency->lastModified();
		}, $dependencies);

		// Add the last modified time for this asset
		$mtimes[] = $lastModified;

		// Sort the last modified times descending
		usort($mtimes, function($a, $b)
		{
			if ($a->getTimestamp() == $b->getTimestamp())
			{
				return 0;
			}

			return $a->getTimestamp() < $b->getTimestamp() ? 1 : -1;
		});

		// Return the newest last modified time;
		return $mtimes[0];
	}

	public function digest()
	{
		return md5($this->content());
	}

	public function isStatic()
	{
		return $this->static;
	}

	protected function directiveProcessor()
	{
		if (!$this->directiveProcessor)
		{
			$this->directiveProcessor = new DirectiveProcessor($this->pipeline, $this, $this->source);
		}

		return $this->directiveProcessor;
	}

	protected function process()
	{
		if (!empty($this->processedContent)) return;

		// We want to run all the processors on the file without the directives included.
		$content = $this->directiveProcessor()->body();

		// Run pre-processors

		// Run engines
		foreach ($this->extensions as $extension)
		{
			$engine = $this->pipeline->engine($extension);

			if ($engine)
			{
				$content = $engine->process($this, $content);
			}
		}

		// Run post-processors

		$this->processedContent = $content;
	}

	protected function bundle()
	{
		if (!empty($this->bundledContent)) return;

		$requiredAssets = $this->directiveProcessor()->requiredAssets();
		$content = '';
		$bodyWritten = false;

		foreach ($requiredAssets as $asset)
		{
			if ($asset == $this)
			{
				$content.= $this->body();
				$bodyWritten = true;
			}
			else
			{
				$content.= $asset->content();
			}

			$content.= PHP_EOL;
		}

		if (!$bodyWritten)
		{
			$content.= $this->body();
		}

		$this->bundledContent = $content;
	}
}
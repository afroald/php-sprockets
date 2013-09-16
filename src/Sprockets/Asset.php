<?php namespace Sprockets;

use Sprockets\Exception\AssetNotFoundException;

class Asset {
	protected $static = false;
	protected $processedContent = '';
	protected $bundledContent = '';

	protected $pipeline;

	protected $source;

	protected $directiveProcessor;

	public function __construct(Pipeline $pipeline, $source)
	{
		$this->pipeline = $pipeline;

		if (!is_a($source, "SplFileInfo"))
		{
			$source = new File($source);
		}

		if (!$source->isFile())
		{
			throw new AssetNotFoundException($source->getFilename());
		}

		$this->source = $source;

		// Check if this is a file we can process. If not, treat it as a static asset (image, font, etc.)
		// Assume only css and js files need to be processed for now.
		if (in_array($this->mimeType, array('text/css', 'application/javascript')))
		{
			// $this->content = $this->source->get();
		}
		else {
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
	public function name()
	{
		$filename = $this->source->getBasename();
		$extensions = $this->extensions();

		return str_replace(implode(array_slice($extensions, 1)), '', $filename);
	}

	/**
	 * Returns the path of the source relative to the load path
	 * @return string
	 */
	public function logicalPath()
	{
		$path = $this->source->getPath();

		foreach($this->pipeline->loadPaths as $loadPath)
		{
			$path = str_replace($loadPath, '', $path);
		}

		return $path . '/' . $this->name;
	}

	/**
	 * Return an array of assets that are required in this asset.
	 * @return array
	 */
	public function dependencies()
	{
		return $this->static ? array($this) : $this->directiveProcessor()->dependencies();
	}

	/**
	 * Return the mime-type of the asset.
	 *
	 * First try to resolve the mime-type
	 * according to the file extension. If that doesn't work use the FileInfo
	 * PECL extension. Return text/html if not able to determine the mime-type
	 *
	 * @return string
	 */
	public function mimeType()
	{
		$mimeTypes = $this->pipeline->mimeTypes();
		$extensions = $this->extensions();

		if (array_key_exists($extensions[0], $mimeTypes)) {
			return $mimeTypes[$extensions[0]];
		}

		if (function_exists('finfo_file')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = finfo_file($finfo, $this->source->getPathname());
			finfo_close($finfo);

			return $mimeType;
		}

		return 'text/html';
	}

	/**
	 * Return the time this asset or one of its depentencies was modified.
	 * @return DateTime
	 */
	public function lastModified()
	{
		$dependencies = $this->dependencies;

		$lastModified = new \DateTime();
		$lastModified->setTimestamp($this->source->getMTime());

		// Return the last modified time of the current asset if there are no dependencies
		if (count($dependencies) == 0) return $lastModified;

		// Collect last modified times for all dependencies
		$mtimes = array_map(function($dependency)
		{
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

	protected function directiveProcessor()
	{
		if (!$this->directiveProcessor)
		{
			$this->directiveProcessor = new DirectiveProcessor($this->pipeline, $this, $this->source);
		}

		return $this->directiveProcessor;
	}

	protected function extensions()
	{
		$matches = array();

		preg_match_all('/\.[^.]+/', $this->source->getBasename(), $matches);

		return $matches[0];
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
				$content = $engine->process($content);
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
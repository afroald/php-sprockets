<?php namespace Sprockets;

use SplFileInfo;
use Sprockets\Exception\AssetNotFoundException;
use Symfony\Component\Finder\Finder as SymfonyFinder;

class Finder {
	protected $loadPaths;

	protected $typeExtensions = array(
		'stylesheet' => '.css',
		'javascript' => '.js'
	);

	public function __construct($loadPaths)
	{
		$this->loadPaths = $loadPaths;
	}

	public function find($logicalPath, $type = null)
	{
		$searchPath = $logicalPath;

		if ($type && !pathinfo($logicalPath, PATHINFO_EXTENSION))
		{
			$searchPath.= $this->typeExtensions[$type];
		}

		$searchPath = new SplFileInfo($searchPath);

		$finder = $this->newFinder();
		$finder->path($searchPath->getPath());
		$finder->name($searchPath->getBasename() . ($searchPath->getExtension() == "" ? '.*' : '*'));

		$iterator = $finder->getIterator();
		$iterator->rewind();

		return $iterator->current();
	}

	public function all()
	{
		$files = iterator_to_array($this->newFinder());

		return array_values($files);
	}

	public function __invoke($name)
	{
		return $this->find($name);
	}

	protected function newFinder()
	{
		return SymfonyFinder::create()->ignoreDotFiles(true)->files()->in($this->loadPaths);
	}
}
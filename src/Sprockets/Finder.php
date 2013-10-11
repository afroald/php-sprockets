<?php namespace Sprockets;

use SplFileInfo;
use Sprockets\Exception\AssetNotFoundException;

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

	public function find($name, $type = null)
	{
		if ($type && !with(new SplFileInfo($name))->getExtension())
		{
			$name.= $this->typeExtensions[$type];
		}

		foreach ($this->loadPaths as $loadPath)
		{
			$path = $loadPath . (with(new SplFileInfo($name))->getExtension() ? "/$name*" : "/$name.*");

			$files = glob($path);

			if (count($files) > 0)
			{
				$file = new SplFileInfo($files[0]);

				return $file->getRealPath();
			}
		}

		throw new AssetNotFoundException($name);
	}

	public function all()
	{
		$finder = \Symfony\Component\Finder\Finder::create();
		$files = iterator_to_array($finder->files()->in($this->loadPaths));

		return array_values($files);
	}

	public function __invoke($name)
	{
		return $this->find($name);
	}
}
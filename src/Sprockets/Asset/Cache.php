<?php namespace Sprockets\Asset;

use SplFileInfo;
use Sprockets\Asset;
use Sprockets\File;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder as SymfonyFinder;

class Cache {

	protected $valid = true;
	protected $cachePath;

	public function __construct($cachePath)
	{
		$this->cachePath = new SplFileInfo($cachePath);

		$filesystem = new Filesystem();
		if (!$this->cachePath->isDir())
		{
			try
			{
				$filesystem->mkdir($this->cachePath->getPathname());
			}
			catch(Symfony\Component\Filesystem\Exception\IOException $exception)
			{
				$this->valid = false;
			}
		}
	}

	public function isValid()
	{
		return $this->valid;
	}

	public function hasContent(Asset $asset)
	{
		$file = $this->getFileForAsset($asset);

		return $file->getMTime() >= $asset->lastModified->getTimestamp();
	}

	public function getContent(Asset $asset)
	{
		$file = $this->getFileForAsset($asset);

		return $file->get();
	}

	public function storeContent(Asset $asset, $content)
	{
		$file = $this->getFileForAsset($asset);
		krumo($file->getPathname());
		$file->put($content);

		return $content;
	}

	protected function getFileForAsset(Asset $asset)
	{
		return new File(sprintf('%s/%s', $this->cachePath->getPathname(), sha1($asset->filename)));
	}
}
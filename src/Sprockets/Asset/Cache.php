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
		$file = $this->getFileForAssetContent($asset);

		if (!$file->isFile())
			return false;

		return $file->getMTime() >= $asset->lastModified->getTimestamp();
	}

	public function getContent(Asset $asset)
	{
		$file = $this->getFileForAssetContent($asset);

		return $file->get();
	}

	public function storeContent(Asset $asset, $content)
	{
		$file = $this->getFileForAssetContent($asset);
		$file->put($content);

		return $content;
	}

	protected function getFileForAssetContent(Asset $asset)
	{
		return new File(sprintf('%s/%s', $this->cachePath->getPathname(), sha1($asset->logicalPathname . 'content')));
	}

	public function hasBody(Asset $asset)
	{
		$file = $this->getFileForAssetBody($asset);

		if (!$file->isFile())
			return false;

		return $file->getMTime() >= $asset->lastModified->getTimestamp();
	}

	public function getBody(Asset $asset)
	{
		$file = $this->getFileForAssetBody($asset);

		return $file->get();
	}

	public function storeBody(Asset $asset, $body)
	{
		$file = $this->getFileForAssetBody($asset);
		$file->put($body);

		return $body;
	}

	protected function getFileForAssetBody(Asset $asset)
	{
		return new File(sprintf('%s/%s', $this->cachePath->getPathname(), sha1($asset->logicalPathname . 'body')));
	}
}
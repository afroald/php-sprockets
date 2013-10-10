<?php namespace Sprockets;

use SplFileInfo;

class File extends SplFileInfo
{
	protected $content = '';
	protected $lastRead;

	public function get()
	{
		if (empty($this->content) || $this->getMTime() > $this->lastRead)
		{
			$this->content = file_get_contents($this->getPathname());
			$this->lastRead = $this->getMTime();
		}

		return $this->content;
	}

	public function put($content)
	{
		$path = new SplFileInfo($this->getPath());
		if (!$path->isDir())
		{
			mkdir($path, 0777, true);
		}

		return file_put_contents($this->getPathname(), $content);
	}
}
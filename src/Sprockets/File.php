<?php namespace Sprockets;

use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;

class File extends SplFileInfo
{
	public function get()
	{
		$level = error_reporting(0);
        $content = file_get_contents($this->getPathname());
        error_reporting($level);
        if (false === $content) {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
        }

        return $content;
	}

	public function put($content, $mode = 0666)
	{
		$filesystem = new Filesystem();
		$filesystem->dumpFile($this->getPathname(), $content, $mode);
	}
}
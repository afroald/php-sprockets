<?php namespace Sprockets;

use SplFileInfo;

class File extends SplFileInfo
{
    public function get()
    {
        return file_get_contents($this->getRealPath());
    }

    public function put($content)
    {
        return file_put_contents($this->getRealPath(), $content);
    }
}
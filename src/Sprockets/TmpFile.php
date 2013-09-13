<?php namespace Sprockets;

class TmpFile extends File {
    public function __construct($prefix = 'sprockets')
    {
        $tmpdir = sys_get_temp_dir();
        $tmpfile = tempnam($tmpdir, $prefix);

        parent::__construct($tmpfile);
    }

    public function __destruct()
    {
        if ($this->isFile()) {
            unlink($this->getRealPath());
        }
    }
}
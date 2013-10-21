<?php namespace Sprockets;

class TmpFile extends File {
	public function __construct($tmpDir = null, $prefix = 'sprockets')
	{
		$tmpDir = $tmpDir ?: sys_get_temp_dir();
		$tmpFile = tempnam($tmpDir, $prefix);

		parent::__construct($tmpFile);
	}

	public function __destruct()
	{
		if ($this->isFile()) {
			unlink($this->getRealPath());
		}
	}
}
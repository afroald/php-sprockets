<?php namespace Sprockets\Filter;

use Sprockets\Asset;
use Sprockets\TmpFile;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\RuntimeException;

class BaseProcessFilter implements FilterInterface {

	public function process(Asset $asset, $content)
	{
		$tmpFile = new TmpFile();
		$tmpFile->put($content);

		$builder = new ProcessBuilder($this->command($asset, $tmpFile));
		$process = $builder->getProcess();
		$process->run();

		if (!$process->isSuccessful())
		{
			throw new RuntimeException($process->getErrorOutput());
		}

		return $process->getOutput();
	}

}
<?php namespace Sprockets\Engine;

use Sprockets\Processor;
use Sprockets\TmpFile;

class ScssEngine extends Processor {
	public function process($content)
	{
		$tmpFile = new TmpFile();
        $tmpFile->put($content);

        $command = "compass compile {$tmpFile} --sass-dir {$tmpFile->getPath()}";

        $output = array();
        exec($command, $output);

        krumo($output);

		return $content;
	}
}
<?php namespace Sprockets\Engine;

use Sprockets\Asset;
use Sprockets\Processor;

class LessEngine extends Processor {
	public function process(Asset $asset, $content)
	{
		$compiler = new \lessc();
		$compiler->addImportDir($asset->path());

		return $compiler->compile($content);
	}
}
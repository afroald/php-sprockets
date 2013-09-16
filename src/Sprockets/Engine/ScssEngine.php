<?php namespace Sprockets\Engine;

use Sprockets\Asset;
use Sprockets\Processor;

class ScssEngine extends Processor {
	public function process(Asset $asset, $content)
	{
		$compiler = new \scssc();
		new \scss_compass($compiler);

		return $compiler->compile($content);
	}
}
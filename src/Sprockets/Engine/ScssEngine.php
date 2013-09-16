<?php namespace Sprockets\Engine;

use Sprockets\Processor;

class ScssEngine extends Processor {
	public function process($content)
	{
		$compiler = new \scssc();
		new \scss_compass($compiler);

		return $compiler->compile($content);
	}
}
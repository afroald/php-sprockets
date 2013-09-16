<?php namespace Sprockets\Engine;

use Sprockets\Processor;
use CoffeeScript\Compiler;

class CoffeeScriptEngine extends Processor {
	public function process($content)
	{
		return Compiler::compile($content, array(
			'filename' => 'asset pipeline',
			'header' => false
		));
	}
}
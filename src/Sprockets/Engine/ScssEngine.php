<?php namespace Sprockets\Engine;

use Sprockets\Processor;

class ScssEngine extends Processor {
	public function process($content)
	{
		$options = array(
			'style' => 'nested',
			'cache' => false,
			'syntax' => 'scss',
			'debug' => false
		);

		$parser = new \SassParser($options);

		return $parser->toCss($content, false);
	}
}
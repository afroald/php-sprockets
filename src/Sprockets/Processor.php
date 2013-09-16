<?php namespace Sprockets;

class Processor implements ProcessorInterface {
	protected $pipeline;

	public function __construct(Pipeline $pipeline)
	{
		$this->pipeline = $pipeline;
	}

	public function process($content)
	{
		return $content;
	}
}
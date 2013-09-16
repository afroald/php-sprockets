<?php namespace Sprockets;

interface ProcessorInterface {
	public function process(Asset $asset, $content);
}
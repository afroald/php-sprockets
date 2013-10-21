<?php namespace Sprockets\Filter;

use Sprockets\Asset;

interface FilterInterface {
	public function process(Asset $asset, $content);
}
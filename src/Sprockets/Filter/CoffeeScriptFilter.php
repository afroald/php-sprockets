<?php namespace Sprockets\Filter;

class CoffeeScriptFilter extends BaseProcessFilter {

	protected function command($asset, $tmpFile)
	{
		return array('coffee', '-cpl', $tmpFile->getRealPath());
	}

}
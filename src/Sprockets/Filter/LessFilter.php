<?php namespace Sprockets\Filter;

class LessFilter extends BaseProcessFilter {

	protected function command($asset, $tmpFile)
	{
		return array(
			'lessc',
			"-rp={$asset->path}",
			"--include-path={$asset->path}",
			$tmpFile->getRealPath()
		);
	}

}
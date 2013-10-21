<?php namespace Sprockets\Filter;

use Sprockets\Asset;
use Sprockets\TmpFile;

class LessFilter extends BaseProcessFilter {

	protected function command(Asset $asset, TmpFile $tmpFile)
	{
		return array(
			'lessc',
			"-rp={$asset->path}",
			"--include-path={$asset->path}",
			$tmpFile->getRealPath()
		);
	}

}
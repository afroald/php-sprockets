<?php namespace Sprockets\Filter;

use Sprockets\Asset;
use Sprockets\TmpFile;

class ScssFilter extends BaseProcessFilter {

	protected function command(Asset $asset, TmpFile $tmpFile)
	{
		return array(
			'scss',
			'--no-cache',
			'--compass',
			'--load-path',
			$asset->path,
			$tmpFile->getRealPath()
		);
	}

}
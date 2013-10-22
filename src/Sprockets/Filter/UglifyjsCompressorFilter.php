<?php namespace Sprockets\Filter;

use Sprockets\Asset;
use Sprockets\TmpFile;

class UglifyjsCompressorFilter extends BaseProcessFilter {

	protected function command(Asset $asset, TmpFile $tmpFile)
	{
		return array(
			'uglifyjs',
			$tmpFile->getRealPath(),
			'-m',
			'-c'
		);
	}

}
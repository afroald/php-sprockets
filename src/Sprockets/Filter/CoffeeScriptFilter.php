<?php namespace Sprockets\Filter;

use Sprockets\Asset;
use Sprockets\TmpFile;

class CoffeeScriptFilter extends BaseProcessFilter {

	protected $bare = false;

	public function __construct($config = array())
	{
		if (array_key_exists('bare', $config))
		{
			$this->bare = $config['bare'];
		}
	}

	protected function command(Asset $asset, TmpFile $tmpFile)
	{
		$options = array('c', 'p');

		if ($this->bare)
		{
			$options[] = 'b';
		}

		return array('coffee', '-'.implode($options), $tmpFile->getRealPath());
	}

}
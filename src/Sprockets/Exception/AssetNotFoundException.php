<?php namespace Sprockets\Exception;

use Exception;

class AssetNotFoundException extends Exception {
	public function __construct($name, $code = 0, $previous = null)
	{
		parent::__construct("Asset [$name] could not be found.", $code, $previous);
	}
}
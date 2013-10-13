<?php namespace Sprockets\Asset;

use SplFileInfo;

class LogicalPath extends SplFileInfo {

	const EXTENSIONS_PATTERN = '/\.([^.]+)/';

	public function __construct($logicalPath)
	{
		$tmp = new SplFileInfo($logicalPath);

		$matches = array();
		preg_match_all(self::EXTENSIONS_PATTERN, $tmp->getBasename(), $matches);
		$extensions = $matches[0];

		$basename = $tmp->getBasename(implode($extensions, ''));

		parent::__construct(($tmp->getPath() ? "{$tmp->getPath()}/" : '') . "{$basename}{$extensions[0]}");
	}

}
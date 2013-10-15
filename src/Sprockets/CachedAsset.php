<?php namespace Sprockets;

use Sprockets\Asset\Cache;

class CachedAsset extends Asset {

	protected $cache;

	public function __construct(Pipeline $pipeline, $source, $logicalPath, Cache $cache)
	{
		parent::__construct($pipeline, $source, $logicalPath);

		$this->cache = $cache;
	}

	public function content()
	{
		if ($this->static)
		{
			return parent::content();
		}

		if ($this->cache->hasContent($this))
		{
			return $this->cache->getContent($this);
		}

		return $this->cache->storeContent($this, parent::content());
	}

}
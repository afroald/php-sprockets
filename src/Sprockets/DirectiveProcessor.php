<?php namespace Sprockets;

use CHH\Shellwords;

class DirectiveProcessor {
	const HEADER_PATTERN = "%^(//.*\n|#.*\n|/\*[\s\S]*?\*/)*%";
	const DIRECTIVE_PATTERN = "%^ \W* = \s* (\w+.*?) (\*/)? $%x";

	protected $directiveFunctions = array(
		'require' => 'processRequireDirective',
		'require_self' => 'processRequireSelfDirective'
	);

	protected $processed = false;

	protected $pipeline;
	protected $asset;
	protected $source;

	protected $dependencies = array();
	protected $requiredAssets = array();

	public function __construct(Pipeline $pipeline, Asset $asset, File $source)
	{
		$this->pipeline = $pipeline;
		$this->asset = $asset;
		$this->source = $source;
	}

	public function dependencies()
	{
		$this->process();

		if (array_search($this->asset, $this->dependencies) === false)
		{
			$this->dependOn($this->asset);
		}

		return $this->dependencies;
	}

	public function requiredAssets()
	{
		$this->process();

		if (array_search($this->asset, $this->requiredAssets) === false)
		{
			$this->requireAsset($this->asset);
		}

		return $this->requiredAssets;
	}

	public function body()
	{
		$content = $this->source->get();
		$directives = $this->directives();

		$lines = preg_split('/\r\n|\n|\r/', $content);

		foreach ($directives as $lineNumber => $directive)
		{
			unset($lines[$lineNumber - 1]);
		}

		return implode(PHP_EOL, $lines);
	}

	protected function header()
	{
		$matches = array();

		if (1 === preg_match(self::HEADER_PATTERN, $this->source->get(), $matches))
		{
			return $matches[0];
		}

		return '';
	}

	protected function directives()
	{
		$directives = array();
		$header = $this->header();

		$lineNumber = 0;

		foreach(preg_split('/\r\n|\n|\r/', $header) as $line)
		{
			$lineNumber += 1;
			$matches = array();

			if (1 === preg_match(self::DIRECTIVE_PATTERN, $line, $matches))
			{
				$arguments = Shellwords::split($matches[1]);
				$directive = array_shift($arguments);

				if (array_key_exists($directive, $this->directiveFunctions))
				{
					$directives[$lineNumber] = array(
						'directive' => $directive,
						'arguments' => $arguments
					);
				}
			}
		}

		return $directives;
	}

	protected function dependOn(Asset $asset)
	{
		if ($asset == $this->asset) return;

		$this->dependencies[] = $asset;
	}

	protected function requireAsset(Asset $asset)
	{
		$this->dependOn($asset);

		$this->requiredAssets[] = $asset;
	}

	protected function process()
	{
		if ($this->processed) return;

		$directives = $this->directives();

		foreach ($directives as $lineNumber => $directive)
		{
			$directiveFunction = $this->directiveFunctions[$directive['directive']];

			call_user_func_array(array($this, $directiveFunction), $directive['arguments']);
		}

		$this->processed = true;
	}

	protected function processRequireDirective($path)
	{
		$asset = $this->pipeline->asset($path);

		$this->requireAsset($asset);
	}

	protected function processRequireSelfDirective()
	{
		$this->requireAsset($this->asset);
	}
}
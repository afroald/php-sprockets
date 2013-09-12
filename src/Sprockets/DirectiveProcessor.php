<?php namespace Sprockets;

use Sprockets\Asset;
use Sprockets\Pipeline;
use CHH\Shellwords;

class DirectiveProcessor {
	const HEADER_PATTERN = "%^(//.*\n|#.*\n|/\*[\s\S]*?\*/)*%";
	const DIRECTIVE_PATTERN = "%^ \W* = \s* (\w+.*?) (\*/)? $%x";

	protected $directiveFunctions = array(
		'require' => 'processRequireDirective',
		'require_self' => 'processRequireSelfDirective'
	);

	protected $pipeline;

	protected $content;
	protected $processedContent = '';

	public function __construct(Pipeline $pipeline)
	{
		$this->pipeline = $pipeline;
	}

	public function process($content)
	{
		$this->content = $content;
		$directives = $this->directives();

		// If no require_self directive is present add it to the end to make sure the asset always includes its own body.
		if(count(array_filter($directives, function($directive)
		{
			return $directive['directive'] == 'require_self';
		})) < 1) { // Anonymous functions are awesome but I still have to figure out a readable way to use them.
			array_push($directives, array(
				'directive' => 'require_self',
				'arguments' => array()
			));
		}

		foreach ($directives as $directive)
		{
			if (!empty($this->processedContent))
			{
				$this->processedContent.= "\n";
			}

			$directiveFunction = $this->directiveFunctions[$directive['directive']];

			call_user_func_array(array($this, $directiveFunction), $directive['arguments']);
		}

		$processedContent = $this->processedContent;

		$this->content = '';
		$this->processedContent = '';

		return $processedContent;
	}

	public function dependencies($content)
	{
		$this->content = $content;

		// Filter the directives to not contain require_self
		$directives = array_filter($this->directives(), function($directive)
		{
			return $directive['directive'] !== 'require_self';
		});

		// Load assets for all directives
		$pipeline = $this->pipeline;
		$dependencies = array_map(function($directive) use($pipeline)
		{
			$file = $pipeline->finder->find($directive['arguments'][0]);
			return new Asset($pipeline, $file);
		}, $directives);

		$this->content = '';

		return $dependencies;
	}

	public function stripDirectives($content)
	{
		$this->content = $content;

		$lines = explode("\n", $this->content);

		foreach ($this->directives() as $lineNumber => $directive)
		{
			unset($lines[$lineNumber - 1]);
		}

		$this->content = '';

		return implode("\n", $lines);
	}

	protected function header()
	{
		$matches = array();

		if (1 === preg_match(self::HEADER_PATTERN, $this->content, $matches))
		{
			return $matches[0];
		}

		return '';
	}

	protected function directives()
	{
		$directives = array();
		$classMethods = get_class_methods($this);

		$header = $this->header();

		$lineNumber = 0;

		foreach(explode("\n", $header) as $line)
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

	protected function processRequireDirective($path)
	{
		$file = $this->pipeline->finder->find($path);

		$asset = new Asset($this->pipeline, $file);

		$this->processedContent.= (string) $asset;
	}

	protected function processRequireSelfDirective()
	{
		$this->processedContent.= $this->stripDirectives($this->content);
	}
}
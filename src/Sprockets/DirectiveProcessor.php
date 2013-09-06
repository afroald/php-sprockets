<?php namespace Sprockets;

use Sprockets\Pipeline;
use Sprockets\Processor;

use CHH\Shellwords;

class DirectiveProcessor extends Processor {
    const HEADER_PATTERN = "%^(//.*\n|#.*\n|/\*[\s\S]*?\*/)*%";
    const DIRECTIVE_PATTERN = "%^ \W* = \s* (\w+.*?) (\*/)? $%x";

    protected $directiveFunctions = array(
        'require' => 'processRequireDirective',
        'require_self' => 'processRequireSelfDirective'
    );

    protected $source;
    protected $content = '';

    public function process($content)
    {
        $this->source = $content;

        $directives = $this->directives();

        // If no require_self directive is present add it to the end.
        if(count(array_filter($directives, function($directive) {
            return $directive['directive'] == 'require_self';
        })) < 1) { // Anonymous functions are awesome but I still have to figure out a readable way to use them.
            array_push($directives, array(
                'directive' => 'require_self',
                'arguments' => array()
            ));
        }

        foreach ($directives as $directive)
        {
            if (!empty($this->content))
            {
                $this->content.= "\n";
            }

            $directiveFunction = $this->directiveFunctions[$directive['directive']];

            call_user_func_array(array($this, $directiveFunction), $directive['arguments']);
        }

        return $this->content;
    }

    protected function header()
    {
        $matches = array();

        if (1 === preg_match(self::HEADER_PATTERN, $this->source, $matches))
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

        $this->content.= (string) $asset;
    }

    protected function processRequireSelfDirective()
    {
        $lines = explode("\n", $this->source);

        foreach ($this->directives() as $lineNumber => $directive)
        {
            unset($lines[$lineNumber - 1]);
        }

        $this->content.= implode("\n", $lines);
    }
}
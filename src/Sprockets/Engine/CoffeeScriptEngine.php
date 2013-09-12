<?php namespace Sprockets\Engine;

use Sprockets\Processor;

class CoffeeScriptEngine extends Processor {
    public function process($content)
    {
        return "COFFEESCRIPT: " . $content;
    }
}
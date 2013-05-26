<?php namespace Sprockets\Engine;

use Sprockets\Processor;

class CoffeeScript extends Processor {
    public function process($content)
    {
        return "COFFEESCRIPT: " . $content;
    }
}
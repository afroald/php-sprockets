<?php namespace Sprockets\Engine;

use Sprockets\Processor;

class Scss extends Processor {
    public function process($content)
    {
        $compiler = new \scssc();

        return $compiler->compile($content);
    }
}
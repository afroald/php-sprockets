<?php namespace Sprockets\Engine;

use Sprockets\Processor;

class ScssEngine extends Processor {
    public function process($content)
    {
        $compiler = new \scssc();

        return $compiler->compile($content);
    }
}
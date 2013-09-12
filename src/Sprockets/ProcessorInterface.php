<?php namespace Sprockets;

use Sprockets\Pipeline;

interface ProcessorInterface {
    public function process($content);
}
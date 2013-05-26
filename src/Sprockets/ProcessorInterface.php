<?php namespace Sprockets;

use Sprockets\Pipeline;

interface ProcessorInterface {
    public function __construct(Pipeline $pipeline);

    public function process($content);
}
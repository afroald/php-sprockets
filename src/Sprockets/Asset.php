<?php namespace Sprockets;

use Sprockets\Exception\AssetNotFoundException;
use Sprockets\DirectiveProcessor;
use Sprockets\File;
use Sprockets\Pipeline;

class Asset {
    public $content;

    protected $pipeline;

    protected $source;

    public function __construct(Pipeline $pipeline, $source)
    {
        $this->pipeline = $pipeline;

        if (!is_a($source, "SplFileInfo"))
        {
            $source = new File($source);
        }

        if (!$source->isFile())
        {
            throw new AssetNotFoundException($source->getFilename());
        }

        $this->source = $source;

        $this->content = $this->source->get();
    }

    public function build()
    {
        $pipeline = $this->pipeline;
        $content = $this->content;

        // Run pre-processors

        // Run engines
        foreach ($this->engines() as $engineClass)
        {
            $engine = new $engineClass($this->pipeline);

            $content = $engine->process($content);
        }

        // Run directive processor
        $directiveProcessor = new DirectiveProcessor($pipeline);

        $content = $directiveProcessor->process($content);

        // Run post-processors

        $this->content = $content;

        return $this;
    }

    public function write()
    {
        // Run compressors if needed
        // Write contents to file

        return $this;
    }

    public function __toString()
    {
        return $this->content;
    }

    public function __get($name) {
        if ($name == 'name')
        {
            return $this->source->getBasename('.' . $this->source->getExtension());
        }

        if ($name == 'path')
        {
            return $this->source->getRealPath();
        }

        return $this->$name;
    }

    public function extensions()
    {
        $matches = array();

        preg_match_all('/\.[^.]+/', $this->source->getBasename(), $matches);

        return $matches[0];
    }

    public function engines()
    {
        return array_intersect_key($this->pipeline->engines(), array_fill_keys($this->extensions(), null));
    }
}
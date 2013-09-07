<?php namespace Sprockets;

use Sprockets\Exception\AssetNotFoundException;
use Sprockets\DirectiveProcessor;
use Sprockets\File;
use Sprockets\Pipeline;

class Asset {
    protected $content;
    protected $processedContent;

    protected $pipeline;

    protected $directiveProcessor;

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
        $content = $this->directiveProcessor()->process();

        // Run post-processors

        $this->processedContent = $content;

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
        return empty($this->processedContent) ? $this->content : $this->processedContent;
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

    /**
     * Return the mime-type of the asset.
     *
     * First try to resolve the mime-type
     * according to the file extension. If that doesn't work use the FileInfo
     * PECL extension. Return text/html if not able to determine the mime-type
     *
     * @return string
     */
    public function mimeType()
    {
        $mimeTypes = $this->pipeline->mimeTypes();
        $extensions = $this->extensions();

        if (array_key_exists($extensions[0], $mimeTypes)) {
            return $mimeTypes[$extensions[0]];
        }

        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $this->source->getPathname());
            finfo_close($finfo);

            return $mimeType;
        }

        return 'text/html';
    }

    public function lastModified()
    {
        $dependencies = $this->directiveProcessor()->dependencies();

        // Return the last modified time of the current asset if there are no dependencies
        if (count($dependencies) == 0)
        {
            $lastModified = new \DateTime();
            $lastModified->setTimestamp($this->source->getMTime());

            return $lastModified;
        }

        // Collect last modified times for all dependencies
        $mtimes = array_map(function($dependency)
        {
            return $dependency->lastModified();
        }, $dependencies);

        // Sort the last modified times
        usort($mtimes, function($a, $b)
        {
            if ($a->getTimestamp() == $b->getTimestamp())
            {
                return 0;
            }

            return $a->getTimestamp() < $b->getTimestamp() ? -1 : 1;
        });

        // Return the newest last modified time;
        return $mtimes[0];
    }

    protected function directiveProcessor()
    {
        if (!$this->directiveProcessor) {
            $this->directiveProcessor = new DirectiveProcessor($this->pipeline, $this->content);
        }

        return $this->directiveProcessor;
    }
}
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

    /**
     * Allow the functions on this object to be accessed as properties.
     * @param  string $property
     * @return mixed
     */
    public function __get($property) {
        if (method_exists($this, $property))
        {
            return $this->$property();
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $property .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_ERROR);
        return null;
    }

    /**
     * Return the processed content.
     * @return string
     */
    public function content()
    {
        $this->build();

        return $this->processedContent;
    }

    /**
     * Return the processed content when this object is typecasted to a string.
     * @return string
     */
    public function __toString()
    {
        return empty($this->processedContent) ? $this->content : $this->processedContent;
    }

    /**
     * Return the processed content excluding the dependencies.
     * @return string
     */
    public function body()
    {
        return $this->directiveProcessor()->stripDirectives();
    }

    /**
     * Return the filename as it would be after processing
     * @return string
     */
    public function name()
    {
        $filename = $this->source->getBasename();
        $extensions = $this->extensions();

        return str_replace(implode(array_slice($extensions, 1)), '', $filename);
    }

    /**
     * Returns the path of the source relative to the load path
     * @return string
     */
    public function logicalPath()
    {
        $path = $this->source->getRealPath();

        foreach($this->pipeline->loadPaths as $loadPath)
        {
            $path = str_replace($loadPath, '', $path);
        }

        return $path;
    }

    /**
     * Return an array of assets that are required in this asset.
     * @return array
     */
    public function dependencies()
    {
        return $this->directiveProcessor()->dependencies();
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

    /**
     * Return the time this asset or one of its depentencies was modified.
     * @return DateTime
     */
    public function lastModified()
    {
        $dependencies = $this->dependencies;

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

    protected function engines()
    {
        return array_intersect_key($this->pipeline->engines(), array_fill_keys($this->extensions(), null));
    }

    protected function extensions()
    {
        $matches = array();

        preg_match_all('/\.[^.]+/', $this->source->getBasename(), $matches);

        return $matches[0];
    }

    protected function build()
    {
        if (!empty($this->processedContent)) return;

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
    }
}
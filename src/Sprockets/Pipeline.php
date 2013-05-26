<?php namespace Sprockets;

use Sprockets\DirectiveProcessor;
use Sprockets\Finder;

class Pipeline {
    public $finder;
    public $directivesProcessor;

    protected $loadPaths = array();

    protected $mimeTypes = array(
        '.css' => 'text/css',
        '.js' => 'application/javascript'
    );
    protected $preProcessors = array();
    protected $postProcessors = array();
    protected $engines = array();
    protected $compressors = array();

    public function __construct(array $loadPaths)
    {
        $this->loadPaths = $loadPaths;

        $this->finder = new Finder($loadPaths);

        // $this->registerPreProcessor('text/css', 'DirectiveProcessor');
        // $this->registerPreProcessor('application/javascript', 'DirectiveProcessor');

        $this->registerEngine('.coffee', 'Sprockets\Engine\CoffeeScript');
        $this->registerEngine('.scss', 'Sprockets\Engine\Scss');
    }

    public function asset($name)
    {
        $file = $this->finder->find($name);

        $asset =  new Asset($this, $file);

        return $asset->build();
    }

    public function mimeTypes()
    {
        return $this->mimeTypes;
    }

    public function engines()
    {
        return $this->engines;
    }

    public function registerMimeType($mimeType, $extension)
    {
        $this->mimeTypes[$extension] = $mimeType;
    }

    public function registerPreProcessor($mimeType, $class)
    {
        if (!array_key_exists($mimeType, $this->preProcessors) or !is_array($this->preProcessors[$mimeType]))
        {
            $this->preProcessors[$mimeType] = array();
        }

        $this->preProcessors[$mimeType][] = $class;
    }

    public function registerPostProcessor($mimeType, $class)
    {

    }

    public function registerEngine($extension, $class)
    {
        $this->engines[$extension] = $class;
    }

    public function registerCompressor($mimeType, $id, $class)
    {
        if (!array_key_exists($mimeType, $this->compressors) or !is_array($this->compressors[$mimeType]))
        {
            $this->compressors[$mimeType] = array();
        }

        $this->compressors[$mimeType][$id] = $class;
    }
}
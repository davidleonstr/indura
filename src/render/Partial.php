<?php
namespace indura\render;

use Exception;

class Partial {
    private string $partialsPath;

    public function __construct(string $partialsPath) 
    {
        if (!file_exists($partialsPath)) {
            throw new Exception('Error: folder ' . $partialsPath . ' does not exist.');
        }

        $this->partialsPath = $partialsPath;
    }

    public function render(string $path, array $args)
    {
        $path = $path . '.php';

        if ($args) {
            extract($args);
        }

        if (!file_exists($this->partialsPath . $path)) {
            throw new Exception('Error: file ' . $this->partialsPath . $path . ' does not exist.');
        }

        ob_start();
        include $this->partialsPath . $path;
        echo ob_get_clean();
    }
}
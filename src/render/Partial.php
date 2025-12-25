<?php
namespace indura\render;

use Exception;

/**
 * Partial
 * 
 * Renderer class for including PHP partial files with dynamic parameters.
 * Allows rendering of reusable PHP templates (partials) by passing variables
 * as an associative array. Uses output buffering to capture and return rendered content.
 */
class Partial {
    /**
     * Base path to the partials folder
     * 
     * @var string
     */
    private string $partialsPath;

    /**
     * Constructor
     * 
     * Initializes the partial renderer with the base path to the partials folder.
     * Validates that the partials directory exists.
     * 
     * @param string $partialsPath Absolute or relative path to the partials folder
     * @throws Exception If the partials folder does not exist
     */
    public function __construct(string $partialsPath) 
    {
        if (!file_exists($partialsPath)) {
            throw new Exception('Error: folder ' . $partialsPath . ' does not exist.');
        }

        $this->partialsPath = $partialsPath;
    }

    /**
     * Renders a partial file with the given parameters
     * 
     * Includes a PHP partial file and makes variables available within its scope.
     * The file extension '.php' is automatically appended to the path.
     * Variables are extracted from the $args array and made available as individual variables.
     * Uses output buffering to capture the partial's output.
     * 
     * @param string $path Path to the partial file relative to partialsPath (without .php extension)
     * @param array $args Associative array of variables to pass to the partial
     * @return void Outputs the rendered partial content
     * @throws Exception If the partial file does not exist
     */
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
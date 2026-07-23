<?php
namespace indura\json;

/**
 * Class for handling JSON files with object-oriented methods and properties.
 */
class File
{
    /**
     * Path to the JSON file
     */
    private string $filepath;
    
    /**
     * JSON file data
     */
    private ?array $data;
    
    /**
     * File loading status
     */
    private bool $loaded;
    
    /**
     * Last error encountered
     */
    private ?string $lastError;

    /**
     * JsonFile class constructor
     *
     * @param string $filepath Path to the JSON file
     */
    public function __construct(string $filepath)
    {
        $this->filepath = $filepath;
        $this->data = null;
        $this->loaded = false;
        $this->lastError = null;
    }

    /**
     * Loads the JSON file content
     *
     * @return bool true if loaded successfully, false otherwise
     */
    public function load(): bool
    {
        $this->lastError = null;
        
        if (!file_exists($this->filepath)) {
            $this->lastError = "File '{$this->filepath}' not found.";
            $this->loaded = false;
            return false;
        }

        $content = file_get_contents($this->filepath);
        if ($content === false) {
            $this->lastError = "Could not read file '{$this->filepath}'.";
            $this->loaded = false;
            return false;
        }

        $this->data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = "JSON decode error: " . json_last_error_msg();
            $this->loaded = false;
            return false;
        }

        $this->loaded = true;
        return true;
    }

    /**
     * Saves current data to the JSON file
     *
     * @param bool $prettyPrint Whether to format JSON with indentation
     * @return bool true if saved successfully, false otherwise
     */
    public function save(bool $prettyPrint = true): bool
    {
        $this->lastError = null;
        
        if ($this->data === null) {
            $this->lastError = "No data to save.";
            return false;
        }

        $flags = JSON_UNESCAPED_UNICODE;
        if ($prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $jsonContent = json_encode($this->data, $flags);
        
        if ($jsonContent === false) {
            $this->lastError = "JSON encode error: " . json_last_error_msg();
            return false;
        }

        $result = file_put_contents($this->filepath, $jsonContent);
        
        if ($result === false) {
            $this->lastError = "Could not write to file '{$this->filepath}'.";
            return false;
        }

        return true;
    }

    /**
     * Gets all JSON data
     *
     * @return array|null Array with data or null if not loaded
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * Sets the JSON data
     *
     * @param array $data Data to set
     */
    public function setData(array $data): void
    {
        $this->data = $data;
        $this->loaded = true;
    }

    /**
     * Gets a specific value using dot notation
     *
     * @param string $key Key in dot notation (e.g., "user.name")
     * @param mixed $default Default value if key is not found
     * @return mixed The found value or default value
     */
    public function get(string $key, $default = null)
    {
        if (!$this->loaded || $this->data === null) {
            return $default;
        }

        $keys = explode('.', $key);
        $current = $this->data;

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return $default;
            }
            $current = $current[$k];
        }

        return $current;
    }

    /**
     * Sets a specific value using dot notation
     *
     * @param string $key Key in dot notation (e.g., "user.name")
     * @param mixed $value Value to set
     */
    public function set(string $key, $value): void
    {
        if ($this->data === null) {
            $this->data = [];
        }

        $keys = explode('.', $key);
        $current = &$this->data;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (!isset($current[$k]) || !is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }

        $this->loaded = true;
    }

    /**
     * Checks if a specific key exists
     *
     * @param string $key Key in dot notation
     * @return bool true if exists, false otherwise
     */
    public function has(string $key): bool
    {
        if (!$this->loaded || $this->data === null) {
            return false;
        }

        $keys = explode('.', $key);
        $current = $this->data;

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return false;
            }
            $current = $current[$k];
        }

        return true;
    }

    /**
     * Removes a specific key
     *
     * @param string $key Key in dot notation
     * @return bool true if removed, false if it didn't exist
     */
    public function remove(string $key): bool
    {
        if (!$this->loaded || $this->data === null) {
            return false;
        }

        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &$this->data;

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return false;
            }
            $current = &$current[$k];
        }

        if (is_array($current) && array_key_exists($lastKey, $current)) {
            unset($current[$lastKey]);
            return true;
        }

        return false;
    }

    /**
     * Checks if the file is loaded
     *
     * @return bool true if loaded, false otherwise
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Gets the file path
     *
     * @return string File path
     */
    public function getFilepath(): string
    {
        return $this->filepath;
    }

    /**
     * Changes the file path
     *
     * @param string $filepath New file path
     */
    public function setFilepath(string $filepath): void
    {
        $this->filepath = $filepath;
        $this->loaded = false;
        $this->data = null;
        $this->lastError = null;
    }

    /**
     * Gets the last error that occurred
     *
     * @return string|null Last error message or null if no errors
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Checks if the file exists
     *
     * @return bool true if exists, false otherwise
     */
    public function exists(): bool
    {
        return file_exists($this->filepath);
    }

    /**
     * Gets file information
     *
     * @return array|null Array with file information or null if it doesn't exist
     */
    public function getFileInfo(): ?array
    {
        if (!$this->exists()) {
            return null;
        }

        $stat = stat($this->filepath);
        return [
            'size' => $stat['size'],
            'modified' => date('Y-m-d H:i:s', $stat['mtime']),
            'created' => date('Y-m-d H:i:s', $stat['ctime']),
            'readable' => is_readable($this->filepath),
            'writable' => is_writable($this->filepath)
        ];
    }
}
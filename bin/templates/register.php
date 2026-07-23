<?php
# Global to register templates
$templates = [];

# Global to register help strings for templates
$helpTemplateStrings = [];

# Function to register templates
function registerTemplate(string $name, string $callable, string $help) {
    global $templates;
    global $helpTemplateStrings;

    $templates[$name] = $callable;
    $helpTemplateStrings[$name] = $help;
}
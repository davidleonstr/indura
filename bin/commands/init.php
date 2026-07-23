<?php
# Include template globals
include dirname(__DIR__) . '/templates/register.php';

# Iterator to auto include template files (including globals)
$templateFiles = new DirectoryIterator(dirname(__DIR__) . '/templates');

# Include templates
foreach ($templateFiles as $file) {
    if ($file->isDot() || !$file->isFile()) {
        continue;
    }

    # Ignore register because they are already included
    if ($file->getFilename() === 'register.php') {
        continue;
    }

    include $file->getPathname();
}

# Command help string (used in help command)
$initHelp = <<<'TXT'
init <template | help> <template-args | template>. Command to generate a project template in your current directory.
    template: Project template name.
        options:
TXT;

# Function to get tabs in string
function getTabs(int $times) {
    # EFFICIENCY
    $whatIsTab = 4;

    # Space as hexadecimal
    $space = "\x20";

    # To use the same separation
    $tab = '';

    # Make tab
    for ($i = 0; $i < $whatIsTab; $i++) {
        $tab = $tab . $space;
    }

    # Final tabs
    $tabs = '';

    # Make tabs
    for ($i = 0; $i <= $times; $i++) {
        $tabs = $tabs . $tab;
    }

    return $tabs;
}

# Adding templates help string
foreach ($helpTemplateStrings as $key => $value) {
    $initHelp = $initHelp . "\n" . getTabs(2) . "$key: $value";
}

# Function to register command
registerCommand('init', 'init', $initHelp);

# Init command
function init(array $args) {
    # Using global
    global $templates;

    # Function to print available templates
    function printTemplates(bool | string $specific = false) {
        global $helpTemplateStrings;

        $templatesString = '';

        if (!$specific) {
            # Adding templates help string
            foreach ($helpTemplateStrings as $key => $value) {
                $templatesString = $templatesString . "\n". getTabs(1) . "$key: $value";
            }  
        } else {
            # If need information about a specific template
            if (array_key_exists($specific, $helpTemplateStrings)) {
                $templatesString = "\n". getTabs(1) . "$specific: $helpTemplateStrings[$specific]";
            } else {
                $templatesString = $templatesString . "\n". getTabs(1) . 'No matches.';
            }
        }

        echo "Available templates:";
        echo $templatesString;
    }

    # From where it's executed
    $cwd = getcwd();

    # Template name
    $selection = $args[2] ?? false;

    # If it don't have a selection
    if (!$selection) {
        echo "Error: No template selected.\n";
        printTemplates();
        return;
    }

    # If just need help
    if ($selection === 'help') {
        printTemplates($args[3] ?? false);
        return;
    }

    # If the selection doesn't match with any template
    if (!isset($templates[$selection])) {
        echo "Error: '$selection' is not an available template.\n";
        printTemplates();
        return;
    }

    # Function to build trees based on dictionary with keys as folders.
    function buildStructure(array $dictionary, string $basePath): void
    {
        foreach ($dictionary as $name => $value) {

            $currentPath = $basePath . DIRECTORY_SEPARATOR . $name;

            if (!pathinfo($name, PATHINFO_EXTENSION)) {

                if (!is_dir($currentPath)) {
                    mkdir($currentPath, 0777, true);
                }
                if (is_array($value)) {
                    buildStructure($value, $currentPath);
                }

            } 
            else {
                file_put_contents($currentPath, (string)$value);
            }
        }
    }

    # Building structure selected
    buildStructure($templates[$selection]($args), $cwd);
    echo "init: Template '$selection' created successfully.";
}
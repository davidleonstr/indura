<?php
# Command help string (used in help command)
$helpHelp = <<<'TXT'
help <command>. Command to view help for other commands.
    command (optional): Command whose help text you want to read.
TXT;

# Function to register command
registerCommand('help', 'help', $helpHelp);

# Help command
function help($args) {
    # Using global help strings
    global $helpStrings;
    
    # Help string
    $helpString = <<<'TXT'
    Indura, just that.

    Available commands:
    TXT;

    # Echo the basic information
    echo $helpString;

    # If needs help of especific command
    if (isset($args[2])) {
        # If the command exists
        if (array_key_exists($args[2], $helpStrings)) {
            echo "\n", $helpStrings[$args[2]], "\n";
            return;
        }
    }

    # Echo every help string in $helpStrings
    foreach ($helpStrings as $value) {
        echo "\n", $value, "\n";
    };
}
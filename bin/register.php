<?php
# Global to register commands
$commands = [];

# Global to register help strings for commands
$helpStrings = [];

# Function to register commands
function registerCommand(string $name, string $callable, string $help) {
    global $commands;
    global $helpStrings;

    $commands[$name] = $callable;
    $helpStrings[$name] = $help;
}
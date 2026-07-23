<?php
# Command help string (used in help command)
$serveHelp = <<<'TXT'
serve <host> <port>. Command to start a server.
    host (optional) (default: 'localhost'): Server host.
    port (optional) (default: '8000'): Server port.
TXT;

# Function to register command
registerCommand('serve', 'serve', $serveHelp);

function serve($args) {
    # Getting args
    $host = $args[2] ?? 'localhost';
    $port = $args[3] ?? 8000;
    $publicPath = 'public';

    $command = sprintf(
        'php -S %s:%d -t %s',
        escapeshellarg($host),
        $port,
        escapeshellarg($publicPath)
    );

    echo "Server started on http://{$host}:{$port}\n";
    echo "Press Ctrl+C to stop the server.\n";

    passthru($command);
}
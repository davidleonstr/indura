<?php
# Function to register template
registerTemplate('basic', 'basic', 'Basic template. (layouts, partials, views). (recomended).');

# Basic template
function basic() {
    # 'basic' Folder
    $basic = [];

    # Making root.php
    $basic['root.php'] = <<<'TXT'
    <?php
    # Load vendor
    require 'vendor/autoload.php';

    # Define a route to get everything relative to the '/'
    define(
        'ROOTPATH', 
        dirname(__FILE__)
    );
    TXT;

    # Making public/index.php
    $basic['public']['index.php'] = <<<'TXT'
    <?php
    # Auto load vendor and constants
    require dirname(__DIR__) . '/root.php';

    # Auto load app
    require dirname(__DIR__) . '/public/app.php';
    TXT;

    # Making public/app.php
    $basic['public']['app.php'] = <<<'TXT'
    <?php
    # Import views
    use indura\router\Views;

    # Initialize views object
    $router = new Views(
        viewsPath: ROOTPATH . '/app/views/', 
        layoutsPath: ROOTPATH . '/app/layouts/'
    );

    # Create GET route called 'home'
    $router->get(
        path: '/', 
        view: 'home', 
        data: [
            # Main layout has title as a parameter
            # Main is the default layout
            'title' => 'Indura PHP'
        ]
    );

    # Execute views router
    $router->run();
    TXT;

    # Making public/src/css/main.css
    $basic['public']['src']['css']['main.css'] = <<<'TXT'
    :root {
        --bg-color: #0f172a;
        --card-bg: #1e293b;
        --text-main: #f8fafc;
        --text-muted: #94a3b8;
        --accent: #38bdf8;
        --border: #334155;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-color);
        color: var(--text-main);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        line-height: 1.6;
    }

    .container {
        text-align: center;
        max-width: 800px;
        padding: 2rem;
        animation: fadeIn 1s ease-out;
    }

    h1 {
        font-size: 4rem;
        font-weight: 600;
        letter-spacing: -1px;
        margin-bottom: 0.5rem;
        background: linear-gradient(to right, #fff, #94a3b8);
        -webkit-background-clip: text; /* ignore */
        -webkit-text-fill-color: transparent;
    }

    p.tagline {
        color: var(--accent);
        font-family: 'JetBrains+Mono', monospace;
        font-size: 1.1rem;
        margin-bottom: 2rem;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .card {
        background-color: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        margin-bottom: 2rem;
    }

    .code-block {
        background: #000;
        padding: 1rem;
        border-radius: 8px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.9rem;
        color: #cbd5e1;
        text-align: left;
        margin: 1.5rem 0;
        border-left: 4px solid var(--accent);
    }

    .btn {
        display: inline-block;
        background-color: var(--text-main);
        color: var(--bg-color);
        padding: 0.8rem 2rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: transform 0.2s, background 0.2s;
    }

    .btn:hover {
        transform: translateY(-2px);
        background-color: var(--accent);
    }

    footer {
        margin-top: 3rem;
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .cat {
    position: fixed;
    font-size: 24px;
    pointer-events: none;
    animation: fall 3s linear forwards;
    }

    @keyframes fall {
    0% {
        transform: translateY(0);
        opacity: 1;
    }
    100% {
        transform: translateY(200px);
        opacity: 0;
    }
    }
    TXT;

    # Making public/src/js/main.js
    $basic['public']['src']['js']['main.js'] = <<<'TXT'
    document.addEventListener('click', e => {
        const cat = document.createElement('div');
        cat.className = 'cat';
        cat.textContent = '🐱';

        cat.style.left = e.clientX + 'px';
        cat.style.top = e.clientY + 'px';

        document.body.appendChild(cat);

        setTimeout(() => cat.remove(), 3000);
    });
    TXT;

    # Making app/layouts/main.php
    $basic['app']['layouts']['main.php'] = <<<'TXT'
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title ?? 'Page Title') ?></title>
        <!-- Every resource starts in the public folder -->
        <link rel="stylesheet" href="/src/css/main.css">
        <script defer src="/src/js/main.js"></script>
        <!-- PHP icon from CDN -->
        <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/2/27/PHP-logo.svg" type="image/svg+xml">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
    </head>
    <body id="root">
        <!-- Main Content -->
        <?= $content # This variable is used to render the content of the views ?>
    </body>
    </html>
    TXT;

    # Making app/partials/code-block.php (partial example)
    $basic['app']['partials']['code-block.php'] = <<<'TXT'
    <div class="code-block" id="typewriter">
        <!-- $code as a parameter (arg from args) -->
        <?= htmlspecialchars($code ?? false); ?>
    </div>
    TXT;

    # Making app/views/home.php
    $basic['app']['views']['home.php'] = <<<'TXT'
    <?php 
    # Import partials renderer
    use indura\render\Partial;

    # Initialize partial renderer
    $partials = new Partial(ROOTPATH . '/app/partials/');
    ?>

    <div class="container">
        <header>
            <h1>Indura</h1>
            <p class="tagline">Lightweight PHP Utilities</p>
        </header>

        <main class="card">
            <p>Welcome to the new era of agile development. <strong>Indura</strong> It is a microframework designed to be robust, fast, and without unnecessary dependencies.</p>
                
            <!-- Just to show -->
            <?= 
            $partials->render(
                path: 'code-block', 
                args: ['code' => 'composer require davidleonstr/indura:dev-main --prefer-source']
            )  
            ?>

            <a href="https://davidleonstr.github.io/indura/" target="_blank" class="btn" id="startBtn">Explore Documentation</a>
        </main>
    </div>
    TXT;

    return $basic;
}
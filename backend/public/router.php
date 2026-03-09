<?php
// Router for PHP built-in server
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $file = __DIR__ . $path;
    
    if ($path !== "/" && file_exists($file)) {
        return false; // serve static file
    }
}

require __DIR__ . "/index.php";

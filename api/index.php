<?php

// 1) Vercel hanya mengizinkan menulis ke /tmp, jadi semua folder tulis Laravel dipindah ke sana.
foreach ([
    '/tmp/storage/app',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

$pengaturan = [
    'LARAVEL_STORAGE_PATH' => '/tmp/storage',
    'VIEW_COMPILED_PATH'   => '/tmp/storage/framework/views',
    'APP_SERVICES_CACHE'   => '/tmp/bootstrap/cache/services.php',
    'APP_PACKAGES_CACHE'   => '/tmp/bootstrap/cache/packages.php',
    'APP_CONFIG_CACHE'     => '/tmp/bootstrap/cache/config.php',
    'APP_ROUTES_CACHE'     => '/tmp/bootstrap/cache/routes.php',
    'APP_EVENTS_CACHE'     => '/tmp/bootstrap/cache/events.php',
    'LOG_CHANNEL'          => 'stderr', // supaya galat tampil di Logs dashboard Vercel
];
foreach ($pengaturan as $kunci => $nilai) {
    putenv("$kunci=$nilai");
    $_ENV[$kunci]    = $nilai;
    $_SERVER[$kunci] = $nilai;
}

// 2) Sajikan aset hasil build Vite (CSS/JS/font) dari public/build lewat PHP.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (str_starts_with($path, '/build/') || in_array($path, ['/favicon.ico', '/robots.txt'], true)) {
    $publik = realpath(__DIR__ . '/../public');
    $file   = realpath($publik . rawurldecode($path));

    if ($file !== false && is_file($file) && str_starts_with($file, $publik . DIRECTORY_SEPARATOR)) {
        $tipe = [
            'css' => 'text/css', 'js' => 'application/javascript', 'json' => 'application/json',
            'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg',
            'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
            'txt' => 'text/plain',
        ];
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($tipe[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=31536000');
        readfile($file);
        exit;
    }
}

// Samakan lingkungan dengan public/index.php, supaya path /api/... tidak dikira bagian dari base URL
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';

require __DIR__ . '/../public/index.php';
<?php

// Pastikan direktori cache dan storage di /tmp siap untuk lingkungan serverless Vercel
$directories = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Forward seluruh HTTP request ke public/index.php milik Laravel
require __DIR__ . '/../public/index.php';

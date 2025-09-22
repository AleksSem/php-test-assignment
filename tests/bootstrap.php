<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Set test environment first
$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_ENV'] = 'test';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    // For tests, use .env.test instead of .env
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env.test');
}

// .env.test is already loaded by bootEnv above

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$basePath = dirname(__DIR__);
$storagePath = getenv('APP_STORAGE_PATH') ?: sys_get_temp_dir().'/siabsoal-storage';
$releaseKeySource = implode('|', [
    $basePath,
    @filemtime($basePath.'/routes/web.php') ?: '',
    @filemtime($basePath.'/composer.lock') ?: '',
]);
$releaseKey = substr(md5($releaseKeySource), 0, 12);
$cachePath = sys_get_temp_dir().'/siabsoal-cache-'.$releaseKey;

foreach ([
    $storagePath.'/app',
    $storagePath.'/app/public',
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
    $cachePath,
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

foreach ([
    'APP_SERVICES_CACHE' => $cachePath.'/services.php',
    'APP_PACKAGES_CACHE' => $cachePath.'/packages.php',
    'APP_CONFIG_CACHE' => $cachePath.'/config.php',
    'APP_ROUTES_CACHE' => $cachePath.'/routes-v7.php',
    'APP_EVENTS_CACHE' => $cachePath.'/events.php',
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require $basePath.'/vendor/autoload.php';

$app = require_once $basePath.'/bootstrap/app.php';
$app->useStoragePath($storagePath);

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);

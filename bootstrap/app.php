<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Lwenjim\Yaf\Application;


try {
    (new Dotenv(__DIR__ . '/../'))->load();
} catch (InvalidPathException $e) {
}

$app = new Application(
    realpath(__DIR__ . '/../')
);

$app->configure('app');
$app->configure('config');
$app->configure('database');

return $app;

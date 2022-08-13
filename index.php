<?php

use App\RequestHandler;

require './vendor/autoload.php';

$config = require './config.php';

if (!isset($config['app_secret'], $config['verify_token'], $config['access_token'])) {
    throw new RuntimeException('Configuration not found');
}

\App\Config::set($config);

$request = \Illuminate\Http\Request::capture();

if ($request->method() === 'GET') {
    [$response, $code] = RequestHandler::handleGET($request);
} elseif ($request->method() === 'POST') {
    [$response, $code] = RequestHandler::handlePOST($request);
} else {
    $response = 'Hello world';
    $code = 200;
}

http_response_code($code);

unset($request);

exit($response);
<?php

require_once __DIR__ . '/../config/bootstrap.php';

use App\Helpers\Logger;
use App\Middleware\ErrorHandler;
use App\Middleware\RequestHandler;

try {
    $request = RequestHandler::handle($_SERVER);
    $router = require __DIR__ . '/../routes/api.php';
    $response = $router->dispatch($request['method'], $request['path']);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    $logger = new Logger();
    $logger->error('Unhandled exception', ['message' => $exception->getMessage()]);
    $response = ErrorHandler::handle($exception);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}

<?php

require_once __DIR__ . '/../config/bootstrap.php';

use App\Http\RequestHelper;
use App\Support\ErrorHandler;
use App\Support\Logger;

try {
    $request = RequestHelper::fromGlobals();
    $router = require __DIR__ . '/../routes/api.php';
    $response = $router->dispatch($request->method(), $request->path());
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
} catch (\Throwable $exception) {
    $logger = new Logger();
    $logger->error('Unhandled exception', ['message' => $exception->getMessage()]);
    $handler = new ErrorHandler(true);
    $response = $handler->handle($exception);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}

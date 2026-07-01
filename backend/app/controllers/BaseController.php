<?php

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Helpers\Logger;

class BaseController
{
    protected Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    protected function json(mixed $data = null, int $status = 200): array
    {
        return JsonResponse::success($data, $status);
    }

    protected function error(string $message, int $status = 500, array $details = []): array
    {
        return JsonResponse::error($message, $status, $details);
    }
}

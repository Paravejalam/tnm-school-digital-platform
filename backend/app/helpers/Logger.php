<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Support\Logger as SupportLogger;

/**
 * Logger helper alias
 *
 * BaseController and legacy helpers reference App\Helpers\Logger.
 * This class delegates to App\Support\Logger (the canonical implementation).
 *
 * Authority: .github/AGENT.md
 * Note: This adapter exists for backwards compatibility with BaseController.
 *       New code should inject App\Support\Logger directly through the container.
 */
class Logger extends SupportLogger
{
    // Inherits all methods from App\Support\Logger.
    // No override needed.
}
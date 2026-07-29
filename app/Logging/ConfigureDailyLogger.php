<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;

class ConfigureDailyLogger
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke($logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                $handler->setFilenameFormat(
                    '{filename}-{date}',
                    'Y-m-d'
                );
                $handler->setFormatter(new LineFormatter(
                    "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                    'Y-m-d H:i:s',
                    true,
                    true
                ));
            }
        }
    }
}

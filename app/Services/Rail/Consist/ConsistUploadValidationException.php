<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use RuntimeException;

final class ConsistUploadValidationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $field = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}

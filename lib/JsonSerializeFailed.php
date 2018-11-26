<?php

declare(strict_types=1);

namespace Lendable\Json;

final class JsonSerializeFailed extends \RuntimeException implements JsonFailure
{
    public function __construct(int $errorCode, string $errorMessage, ?\Throwable $previous = null)
    {
        parent::__construct(
            \sprintf(
                'Failed to serialize data to JSON. Error code: %d, error message: %s.',
                $errorCode,
                $errorMessage
            ),
            $errorCode,
            $previous
        );
    }
}

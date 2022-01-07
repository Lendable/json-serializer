<?php

declare(strict_types=1);

namespace Lendable\Json;

final class DeserializationFailed extends \RuntimeException implements Failure
{
    public function __construct(\JsonException $cause)
    {
        parent::__construct(
            \sprintf(
                'Failed to deserialize data from JSON. Error code: %d, error message: %s.',
                $cause->getCode(),
                $cause->getMessage(),
            ),
            $cause->getCode(),
            $cause,
        );
    }
}

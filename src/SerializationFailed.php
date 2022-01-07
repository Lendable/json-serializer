<?php

declare(strict_types=1);

namespace Lendable\Json;

final class SerializationFailed extends \RuntimeException implements Failure
{
    public function __construct(\JsonException $cause)
    {
        parent::__construct(
            \sprintf(
                'Failed to serialize data to JSON. Error code: %d, error message: %s.',
                $cause->getCode(),
                $cause->getMessage(),
            ),
            $cause->getCode(),
            $cause,
        );
    }
}

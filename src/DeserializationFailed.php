<?php

declare(strict_types=1);

namespace Lendable\Json;

final class DeserializationFailed extends \RuntimeException implements Failure
{
    public function __construct(\JsonException $cause)
    {
        $causeCode = $cause->getCode();
        \assert(\is_int($causeCode));

        parent::__construct(
            \sprintf(
                'Failed to deserialize data from JSON. Error code: %d, error message: %s.',
                $causeCode,
                $cause->getMessage(),
            ),
            $causeCode,
            $cause,
        );
    }
}

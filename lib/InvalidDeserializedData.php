<?php

declare(strict_types=1);

namespace Lendable\Machine\Json\Exception;

use Lendable\Json\Failure;

class InvalidDeserializedData extends \RuntimeException implements Failure
{
    public function __construct(string $unexpectedType)
    {
        parent::__construct(sprintf('Expected array when deserializing JSON, got "%s".', $unexpectedType));
    }
}

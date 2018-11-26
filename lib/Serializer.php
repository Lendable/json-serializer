<?php

declare(strict_types=1);

namespace Lendable\Json;

use Lendable\Machine\Json\Exception\InvalidDeserializedData;

final class Serializer
{
    /**
     * @param mixed $data
     *
     * @throws Failure
     */
    public function serialize($data): string
    {
        $serialized = \json_encode($data);

        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new SerializationFailed(\json_last_error(), \json_last_error_msg());
        }

        \assert(\is_string($serialized));

        return $serialized;
    }

    /**
     * @throws Failure
     */
    public function deserialize(string $json): array
    {
        $data = \json_decode($json, true);

        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new DeserializationFailed(\json_last_error(), \json_last_error_msg());
        }

        if (!\is_array($data)) {
            throw new InvalidDeserializedData(\gettype($data));
        }

        return $data;
    }
}

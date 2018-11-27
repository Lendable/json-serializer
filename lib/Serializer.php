<?php

declare(strict_types=1);

namespace Lendable\Json;

final class Serializer
{
    /**
     * @param mixed $data
     *
     * @throws SerializationFailure
     */
    public function serialize(array $data): string
    {
        $serialized = \json_encode($data);

        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new SerializationFailed(\json_last_error(), \json_last_error_msg());
        }

        \assert(\is_string($serialized));

        return $serialized;
    }

    /**
     * @throws DeserializationFailure
     * @throws InvalidDeserializedData
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

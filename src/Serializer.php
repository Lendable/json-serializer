<?php

declare(strict_types=1);

namespace Lendable\Json;

final readonly class Serializer
{
    /**
     * @phpstan-param array<mixed> $data
     *
     * @throws SerializationFailed
     */
    public function serialize(array $data): string
    {
        try {
            $serialized = \json_encode($data, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $exception) {
            throw new SerializationFailed($exception);
        }

        return $serialized;
    }

    /**
     * @throws DeserializationFailed
     * @throws InvalidDeserializedData
     *
     * @phpstan-return array<mixed>
     */
    public function deserialize(string $json): array
    {
        try {
            $data = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new DeserializationFailed($exception);
        }

        if (!\is_array($data)) {
            throw new InvalidDeserializedData(\gettype($data));
        }

        return $data;
    }
}

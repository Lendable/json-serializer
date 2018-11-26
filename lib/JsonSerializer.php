<?php

declare(strict_types=1);

namespace Lendable\Json;

final class JsonSerializer implements Serializer
{
    public function serialize($data): string
    {
        $serialized = \json_encode($data);

        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonSerializeFailed(\json_last_error(), \json_last_error_msg());
        }

        \assert(\is_string($serialized));

        return $serialized;
    }

    public function deserialize(string $json): array
    {
        $data = \json_decode($json, true);

        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonDeserializeFailed(\json_last_error(), \json_last_error_msg());
        }

        \assert(\is_array($data));

        return $data;
    }
}

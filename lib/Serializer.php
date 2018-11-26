<?php

declare(strict_types=1);

namespace Lendable\Json;

interface Serializer
{
    /**
     * @param mixed $data
     *
     * @throws JsonFailure
     */
    public function serialize($data): string;

    /**
     * @throws JsonFailure
     */
    public function deserialize(string $data): array;
}

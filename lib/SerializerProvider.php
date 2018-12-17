<?php

declare(strict_types=1);

namespace Lendable\Json;

interface SerializerProvider
{
    public function getSerializer(): Serializer;
}

<?php

declare(strict_types=1);

namespace Tests\Lendable\Json\Unit;

use Lendable\Json\DeserializationFailed;
use Lendable\Json\InvalidDeserializedData;
use Lendable\Json\SerializationFailed;
use Lendable\Json\Serializer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Lendable\Json\Serializer
 * @covers \Lendable\Json\SerializationFailed
 * @covers \Lendable\Json\DeserializationFailed
 * @covers \Lendable\Json\InvalidDeserializedData
 */
final class SerializerTest extends TestCase
{
    private Serializer $serializer;

    /**
     * @test
     */
    public function it_can_serialize_an_array_of_scalars_to_json(): void
    {
        $result = $this->serializer->serialize(['foo' => 'bar', 'baz' => [1.03, true, 'foobar'], 'emoji' => '😃', 'with_forward_slash' => 'foo/bar']);

        $this->assertSame('{"foo":"bar","baz":[1.03,true,"foobar"],"emoji":"😃","with_forward_slash":"foo/bar"}', $result);
    }

    /**
     * @test
     */
    public function it_throws_when_serializing_if_an_error_encountered(): void
    {
        $this->expectException(SerializationFailed::class);
        $this->expectExceptionMessage('Failed to serialize data to JSON. Error code: 5, error message: Malformed UTF-8 characters, possibly incorrectly encoded.');

        $this->serializer->serialize(["\xf0\x28\x8c\xbc" => 'bar']);
    }

    /**
     * @test
     */
    public function it_can_deserialize_from_a_json_string_to_php_scalars(): void
    {
        $result = $this->serializer->deserialize('{"foo":"bar","baz":[1.03,true,"foobar"]}');

        $this->assertSame(['foo' => 'bar', 'baz' => [1.03, true, 'foobar']], $result);
    }

    /**
     * @test
     */
    public function it_throws_when_deserializing_if_an_error_encountered(): void
    {
        $this->expectException(DeserializationFailed::class);
        $this->expectExceptionMessage('Failed to deserialize data from JSON. Error code: 4, error message: Syntax error.');

        $this->serializer->deserialize('{"unclosed":"bad","object":"json"');
    }

    /**
     * @test
     */
    public function it_throws_when_deserializing_if_the_result_is_not_an_array(): void
    {
        $this->expectExceptionMessage(InvalidDeserializedData::class);
        $this->expectExceptionMessage('Expected array when deserializing JSON, got "boolean".');

        $this->serializer->deserialize('true');
    }

    protected function setUp(): void
    {
        $this->serializer = new Serializer();
    }
}

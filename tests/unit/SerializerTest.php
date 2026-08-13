<?php

declare(strict_types=1);

namespace Tests\Lendable\Json\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Lendable\Json\DeserializationFailed;
use Lendable\Json\Failure;
use Lendable\Json\InvalidDeserializedData;
use Lendable\Json\SerializationFailed;
use Lendable\Json\Serializer;
use PHPUnit\Framework\TestCase;

#[CoversClass(Serializer::class)]
#[CoversClass(SerializationFailed::class)]
#[CoversClass(DeserializationFailed::class)]
#[CoversClass(InvalidDeserializedData::class)]
final class SerializerTest extends TestCase
{
    private const int MAX_NESTING_DEPTH = 511;

    private Serializer $serializer;

    #[Test]
    public function it_can_serialize_an_array_of_scalars_to_json(): void
    {
        $result = $this->serializer->serialize(['foo' => 'bar', 'baz' => [1.03, true, 'foobar'], 'emoji' => '😃', 'with_forward_slash' => 'foo/bar']);

        $this->assertSame('{"foo":"bar","baz":[1.03,true,"foobar"],"emoji":"😃","with_forward_slash":"foo/bar"}', $result);
    }

    #[Test]
    public function it_can_serialize_an_empty_array_to_json(): void
    {
        $this->assertSame('[]', $this->serializer->serialize([]));
    }

    #[Test]
    public function it_throws_when_serializing_if_an_error_encountered(): void
    {
        $this->expectException(SerializationFailed::class);
        $this->expectExceptionMessageIs('Failed to serialize data to JSON. Error code: 5, error message: Malformed UTF-8 characters, possibly incorrectly encoded.');

        $this->serializer->serialize(["\xf0\x28\x8c\xbc" => 'bar']);
    }

    /**
     * @param array<mixed> $data
     */
    #[Test]
    #[DataProvider('provideUnrepresentableFloats')]
    public function it_throws_when_serializing_a_float_json_cannot_represent(array $data): void
    {
        $this->expectException(SerializationFailed::class);
        $this->expectExceptionMessageIs('Failed to serialize data to JSON. Error code: 7, error message: Inf and NaN cannot be JSON encoded.');

        $this->serializer->serialize($data);
    }

    /**
     * @return iterable<string, array{array<mixed>}>
     */
    public static function provideUnrepresentableFloats(): iterable
    {
        yield 'infinity' => [[\INF]];
        yield 'negative infinity' => [[-\INF]];
        yield 'not a number' => [[\NAN]];
    }

    #[Test]
    public function it_can_deserialize_from_a_json_string_to_php_scalars(): void
    {
        $result = $this->serializer->deserialize('{"foo":"bar","baz":[1.03,true,"foobar"]}');

        $this->assertSame(['foo' => 'bar', 'baz' => [1.03, true, 'foobar']], $result);
    }

    /**
     * @param array<mixed> $expected
     */
    #[Test]
    #[DataProvider('provideEmptyStructures')]
    public function it_can_deserialize_an_empty_structure(string $json, array $expected): void
    {
        $this->assertSame($expected, $this->serializer->deserialize($json));
    }

    /**
     * @return iterable<string, array{string, array<mixed>}>
     */
    public static function provideEmptyStructures(): iterable
    {
        yield 'empty array' => ['[]', []];
        yield 'empty object' => ['{}', []];
    }

    #[Test]
    public function it_throws_when_deserializing_if_an_error_encountered(): void
    {
        $this->expectException(DeserializationFailed::class);
        $this->expectExceptionMessageMatches('/^Failed to deserialize data from JSON\. Error code: 4, error message: Syntax error(?: near location \d+:\d+)?\.$/');

        $this->serializer->deserialize('{"unclosed":"bad","object":"json"');
    }

    #[Test]
    public function it_can_deserialize_json_nested_to_the_maximum_supported_depth(): void
    {
        $result = $this->serializer->deserialize($this->nestedArrays(self::MAX_NESTING_DEPTH));

        $this->assertCount(1, $result);
    }

    #[Test]
    public function it_throws_when_deserializing_json_nested_beyond_the_maximum_supported_depth(): void
    {
        $this->expectException(DeserializationFailed::class);
        $this->expectExceptionMessageMatches('/^Failed to deserialize data from JSON\. Error code: 1, error message: Maximum stack depth exceeded(?: near location \d+:\d+)?\.$/');

        $this->serializer->deserialize($this->nestedArrays(self::MAX_NESTING_DEPTH + 1));
    }

    #[Test]
    #[DataProvider('provideNonArrayRoots')]
    public function it_throws_when_deserializing_if_the_result_is_not_an_array(string $json, string $expectedType): void
    {
        $this->expectException(InvalidDeserializedData::class);
        $this->expectExceptionMessageIs(\sprintf('Expected array when deserializing JSON, got "%s".', $expectedType));

        $this->serializer->deserialize($json);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideNonArrayRoots(): iterable
    {
        yield 'true' => ['true', 'boolean'];
        yield 'false' => ['false', 'boolean'];
        yield 'null' => ['null', 'NULL'];
        yield 'integer' => ['5', 'integer'];
        yield 'float' => ['1.5', 'double'];
        yield 'string' => ['"foo"', 'string'];
    }

    #[Test]
    public function a_serialization_error_can_be_caught_as_a_failure(): void
    {
        $this->expectException(Failure::class);

        $this->serializer->serialize(["\xf0\x28\x8c\xbc" => 'bar']);
    }

    #[Test]
    public function a_deserialization_error_can_be_caught_as_a_failure(): void
    {
        $this->expectException(Failure::class);

        $this->serializer->deserialize('{"unclosed":"bad","object":"json"');
    }

    #[Test]
    public function invalid_deserialized_data_can_be_caught_as_a_failure(): void
    {
        $this->expectException(Failure::class);

        $this->serializer->deserialize('true');
    }

    private function nestedArrays(int $depth): string
    {
        return \str_repeat('[', $depth).\str_repeat(']', $depth);
    }

    protected function setUp(): void
    {
        $this->serializer = new Serializer();
    }
}

<?php

require_once __DIR__.'/Fixtures/Serialization.php';

use Dapr\Deserialization\Attributes\AsClass;
use Dapr\Deserialization\Deserializer;
use Dapr\exceptions\DaprException;

function deserialize_special_type($obj)
{
    return $obj['hello'];
}

/**
 * Class DeserializerTest
 */
final class DeserializerTest extends DaprTests
{
    public function generate_deserializers()
    {
        $obj = new class {
            public string $foo = 'bar';
            public string $bar = 'baz';
        };

        $nested    = new class {
            #[AsClass(DateInterval::class)]
            public DateInterval $a;
        };
        $nested->a = new DateInterval('PT22M');

        $deserialized = json_decode(get_example_json(), true);
        $other_sdk    = get_example_object();
        $nullable     = new class {
            public string $test;
        };

        return [
            'Type'    => [[$obj::class, ['foo' => 'bar', 'bar' => 'baz']], $obj],
            'Nested'  => [[$nested::class, ['a' => 'PT22M']], $nested],
            'Complex' => [[WeatherForecastWithPOPOs::class, $deserialized], $other_sdk],
            'Null'    => [[$nullable::class, null], null],
        ];
    }

    /**
     * @dataProvider generate_deserializers
     */
    public function testDeserializeValues($value, $expected)
    {
        $deserializer = $this->container->get(Deserializer::class);
        $result       = $deserializer->from_value($value[0], $value[1]);
        $this->assertEquals($expected, $result);
    }

    public function testDaprException()
    {
        $deserializer = $this->container->get(Deserializer::class);
        $obj          = [
            'errorCode' => 'ERR_ACTOR_INSTANCE_MISSING',
            'message'   => 'Error getting an actor instance. This means that actor is now hosted in some other service replica.',
        ];
        $this->assertTrue($deserializer->is_exception($obj));
        $exception = $deserializer->get_exception($obj);
        $this->assertSame($obj['errorCode'], $exception->get_dapr_error_code());
        $this->assertSame($obj['message'], $exception->getMessage());
    }

    public function testPHPException()
    {
        $deserializer = $this->container->get(Deserializer::class);
        $obj          = [
            'errorCode' => LogicException::class,
            'message'   => 'should not happen',
            'file'      => __FILE__,
            'line'      => 123,
        ];
        $this->assertTrue($deserializer->is_exception($obj));
        $exception = $deserializer->get_exception($obj);
        $this->assertSame($obj['errorCode'], $exception->get_dapr_error_code());
        $this->assertSame($obj['message'], $exception->getMessage());
        $this->assertSame($obj['file'], $exception->getFile());
        $this->assertSame($obj['line'], $exception->getLine());
        $this->assertSame(null, $exception->getPrevious());
    }

    public function testExceptionChain()
    {
        $deserializer = $this->container->get(Deserializer::class);
        $obj          = [
            'errorCode' => LogicException::class,
            'message'   => 'test message',
            'file'      => __FILE__,
            'line'      => 123,
            'inner'     => [
                'errorCode' => DaprException::class,
                'message'   => 'ok',
                'file'      => __FILE__,
                'line'      => 123,
            ],
        ];
        $this->assertTrue($deserializer->is_exception($obj));
        $exception = $deserializer->get_exception($obj);
        $this->assertInstanceOf(DaprException::class, $exception->getPrevious());
    }
}

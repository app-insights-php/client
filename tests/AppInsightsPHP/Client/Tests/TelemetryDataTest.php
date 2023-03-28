<?php

declare(strict_types=1);

namespace AppInsightsPHP\Client\Tests;

use AppInsightsPHP\Client\TelemetryData;
use PHPUnit\Framework\TestCase;

final class TelemetryDataTest extends TestCase
{
    public static function invalid() : \Generator
    {
        $message = \bin2hex(\random_bytes(35000));

        yield [['name' => $message]];
        yield [['name' => \substr($message, 0, 65000)]];
        yield [['foo' => \substr($message, 0, 12000), 'bar' => \substr($message, 0, 23000), 'fuzz' => \substr($message, 0, 30000)]];
    }

    public function test_do_not_exceed_maximum_size() : void
    {
        $this->assertFalse(
            TelemetryData::message('message', ['foo' => 'foo', 'bar' => 'bar', 'message' => \bin2hex(\random_bytes(10000))])->exceededMaximumSize()
        );
    }

    /**
     * @dataProvider invalid
     */
    public function test_exceed_maximum_size(array $properties) : void
    {
        $telemetry = TelemetryData::message('message', $properties);

        $this->assertTrue($telemetry->exceededMaximumSize());
        $this->expectException(\RuntimeException::class);

        $telemetry->validate();
    }
}

<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client\Tests;

use AppInsightsPHP\Client\ResponseMessage;
use PHPUnit\Framework\TestCase;

final class ResponseMessageTest extends TestCase
{
    public function test_message_contain_is_to_old_time_envelope_string(): void
    {
        $this->assertTrue(
            ResponseMessage::message("Test if this message contain: Field 'time' on type 'Envelope' is older than the allowed min date. Test some post string...")
                ->isToOldTimeInEnvelope()
        );
    }

    /**
     * @dataProvider invalid
     */
    public function test_message_not_contain_is_to_old_time_envelope_string(string $message): void
    {
        $this->assertFalse(
            ResponseMessage::message($message)
                ->isToOldTimeInEnvelope()
        );
    }

    public function invalid(): \Generator
    {
        yield ["Field 'time' on type 'Envelope' is older than the allowed"];
        yield [\uniqid('test-string-')];
        yield ["field 'time' on type 'Envelope' is older than the allowed min date."];
    }
}

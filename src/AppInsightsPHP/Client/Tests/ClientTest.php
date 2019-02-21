<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client\Tests;

use AppInsightsPHP\Client\Client;
use AppInsightsPHP\Client\Configuration;
use ApplicationInsights\Channel\Contracts\Request_Data;
use ApplicationInsights\Telemetry_Client;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function test_tracking_when_client_is_disabled()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault()
        );

        $client->configuration()->disable();

        $telemetryMock->expects($this->never())
            ->method('trackRequest');
        $telemetryMock->expects($this->never())
            ->method('beginRequest');
        $telemetryMock->expects($this->never())
            ->method('endRequest');
        $telemetryMock->expects($this->never())
            ->method('trackMessage');
        $telemetryMock->expects($this->never())
            ->method('trackDependency');
        $telemetryMock->expects($this->never())
            ->method('trackException');

        $client->trackException(new \Exception());
        $client->trackDependency('name');
        $client->trackMessage('message');
        $client->beginRequest('name', 'url', time());
        $client->endRequest(new Request_Data());
    }

    public function test_tracking_request_when_option_is_enabled()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault()
        );

        $telemetryMock->expects($this->once())
            ->method('trackRequest')
            ->with('name', 'url', $this->isFinite(), 0, 200, true, null, null);

        $client->trackRequest('name', 'url', time());
    }

    public function test_tracking_request_when_option_is_disabled()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault()
        );

        $client->configuration()->requests()->disable();

        $telemetryMock->expects($this->never())
            ->method('trackRequest');
        $telemetryMock->expects($this->never())
            ->method('beginRequest');
        $telemetryMock->expects($this->never())
            ->method('endRequest');

        $client->trackRequest('name', 'url', time());
        $client->beginRequest('name', 'url', time());
        $client->endRequest(new Request_Data());
    }

    public function test_tracking_dependencies_when_option_is_enabled()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault()
        );

        $telemetryMock->expects($this->once())
            ->method('trackDependency')
            ->with('name', '', null, null, 0, true, null, null);

        $client->trackDependency('name');
    }

    public function test_tracking_dependencies_when_option_is_disabled()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault()
        );

        $client->configuration()->dependencies()->disable();

        $telemetryMock->expects($this->never())
            ->method('trackDependency');

        $client->trackDependency('name');
    }


    public function test_tracking_exceptions_when_option_is_enabled()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault()
        );

        $exception = new \Exception();

        $telemetryMock->expects($this->once())
            ->method('trackException')
            ->with($this->isInstanceOf(\Exception::class), null, null);

        $client->trackException($exception);
    }

    public function test_tracking_exceptions_when_option_is_disabled()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault()
        );

        $client->configuration()->exceptions()->disable();

        $exception = new \Exception();

        $telemetryMock->expects($this->never())
            ->method('trackException');

        $client->trackException($exception);
    }

    public function test_tracking_exceptions_that_suppose_to_be_ignored()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault()
        );

        $client->configuration()->exceptions()->ignore(\RuntimeException::class);

        $telemetryMock->expects($this->never())
            ->method('trackException')
            ->with($this->isInstanceOf(\RuntimeException::class), null, null);

        $client->trackException(new \RuntimeException());
    }


    public function test_tracking_traces_when_option_is_enabled()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault()
        );

        $telemetryMock->expects($this->once())
            ->method('trackMessage')
            ->with('message', null, null);

        $client->trackMessage('message');
    }

    public function test_tracking_traces_when_option_is_disabled()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault()
        );

        $client->configuration()->traces()->disable();

        $telemetryMock->expects($this->never())
            ->method('trackMessage');

        $client->trackMessage('message');
    }
}
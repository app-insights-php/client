<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client\Tests;

use AppInsightsPHP\Client\Client;
use AppInsightsPHP\Client\Configuration;
use ApplicationInsights\Channel\Contracts\Request_Data;
use ApplicationInsights\Channel\Telemetry_Channel;
use ApplicationInsights\Telemetry_Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

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
            ->with('dependency_name', '', null, null, 0, true, null, null);

        $client->trackDependency('dependency_name');
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

        $client->trackDependency('dependency_name');
    }

    public function test_tracking_dependencies_when_dependency_is_ignored()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault()
        );

        $client->configuration()->dependencies()->ignore('dependency_name');

        $telemetryMock->expects($this->never())
            ->method('trackDependency');

        $client->trackDependency('dependency_name');
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

    public function test_fallback_logger_during_flush_unexpected_exception()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $this->givenTelemetryChannelIsNotEmpty($telemetryMock);
        $telemetryMock->method('flush')->willThrowException(new \RuntimeException('Unexpected API exception'));

        $loggerMock->expects($this->once())
            ->method('error')
            ->with('Exception occurred while flushing App Insights Telemetry Client: Unexpected API exception');

        $client = new Client($telemetryMock, Configuration::createDefault(), null, $loggerMock);
        $client->flush();
    }

    public function test_adding_queue_to_failure_cache_on_unexpected_api_exception_and_cache_is_empty()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $cacheMock = $this->createMock(CacheInterface::class);

        $this->givenTelemetryChannelIsNotEmpty($telemetryMock);
        $telemetryMock->method('flush')->willThrowException(new \RuntimeException('Unexpected API exception'));
        $cacheMock->method('has')->willReturn(false);

        $cacheMock->expects($this->once())
            ->method('set')
            ->with(Client::CACHE_CHANNEL_KEY, serialize(['some_log_entry']), Client::CACHE_CHANNEL_TTL_SEC);

        $client = new Client($telemetryMock, Configuration::createDefault(), $cacheMock, $loggerMock);
        $client->flush();
    }

    public function test_adding_queue_to_failure_cache_on_unexpected_api_exception_and_cache_is_not_empty()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $cacheMock = $this->createMock(CacheInterface::class);

        $this->givenTelemetryChannelIsNotEmpty($telemetryMock);
        $telemetryMock->method('flush')->willThrowException(new \RuntimeException('Unexpected API exception'));
        $cacheMock->method('has')->willReturn(true);
        $cacheMock->method('get')->willReturn(serialize(['some_older_entry']));

        $cacheMock->expects($this->once())
            ->method('set')
            ->with(Client::CACHE_CHANNEL_KEY, serialize(['some_older_entry', 'some_log_entry']), Client::CACHE_CHANNEL_TTL_SEC);

        $client = new Client($telemetryMock, Configuration::createDefault(), $cacheMock, $loggerMock);
        $client->flush();
    }

    public function test_flush_when_cache_is_not_empty()
    {
        $telemetryMock = $this->createMock(Telemetry_Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $cacheMock = $this->createMock(CacheInterface::class);

        $telemetryChannelMock = $this->givenTelemetryChannelIsNotEmpty($telemetryMock);
        $cacheMock->method('has')->willReturn(true);
        $cacheMock->method('get')->willReturn(serialize(['some_older_entry']));

        $cacheMock->expects($this->once())
            ->method('delete')
            ->with(Client::CACHE_CHANNEL_KEY);

        $telemetryChannelMock->expects($this->once())
            ->method('setQueue')
            ->with(['some_older_entry', 'some_log_entry']);

        $telemetryMock->expects($this->once())->method('flush');

        $client = new Client($telemetryMock, Configuration::createDefault(), $cacheMock, $loggerMock);
        $client->flush();
    }

    private function givenTelemetryChannelIsNotEmpty(MockObject $telemetryMock): MockObject
    {
        $telemetryMock->method('getChannel')->willReturn($telemetryChannelMock = $this->createMock(Telemetry_Channel::class));
        $telemetryChannelMock->method('getQueue')->willReturn(['some_log_entry']);
        $telemetryChannelMock->method('getSerializedQueue')->willReturn(json_encode(['some_log_entry']));

        return $telemetryChannelMock;
    }
}
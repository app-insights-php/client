<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client\Tests;

use AppInsightsPHP\Client\Client;
use AppInsightsPHP\Client\Configuration;
use AppInsightsPHP\Client\FailureCache;
use AppInsightsPHP\Client\Tests\Fake\FakeCache;
use ApplicationInsights\Channel\Contracts\Envelope;
use ApplicationInsights\Channel\Contracts\Request_Data;
use ApplicationInsights\Channel\Contracts\Utils;
use ApplicationInsights\Channel\Telemetry_Channel;
use ApplicationInsights\Telemetry_Client;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Handler\MockHandler as GuzzleHttpHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

final class ClientTest extends TestCase
{
    public function test_tracking_when_client_is_disabled()
    {
        $telemetryMock = $this->createTelemetryClientMock();

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault(),
            new FailureCache($this->createMock(CacheInterface::class)),
            new NullLogger()
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
        $telemetryMock = $this->createTelemetryClientMock();

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault(),
            new FailureCache($this->createMock(CacheInterface::class)),
            new NullLogger()
        );

        $telemetryMock->expects($this->once())
            ->method('trackRequest')
            ->with('name', 'url', $this->isFinite(), 0, 200, true, null, null);

        $client->trackRequest('name', 'url', time());
    }

    public function test_tracking_request_when_option_is_disabled()
    {
        $telemetryMock = $this->createTelemetryClientMock();

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault(),
            new FailureCache($this->createMock(CacheInterface::class)),
            new NullLogger()
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
        $telemetryMock = $this->createTelemetryClientMock();

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault(),
            new FailureCache($this->createMock(CacheInterface::class)),
            new NullLogger()
        );

        $telemetryMock->expects($this->once())
            ->method('trackDependency')
            ->with('dependency_name', '', null, null, 0, true, null, null);

        $client->trackDependency('dependency_name');
    }

    public function test_tracking_dependencies_when_option_is_disabled()
    {
        $telemetryMock = $this->createTelemetryClientMock();

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault(),
            new FailureCache($this->createMock(CacheInterface::class)),
            new NullLogger()
        );

        $client->configuration()->dependencies()->disable();

        $telemetryMock->expects($this->never())
            ->method('trackDependency');

        $client->trackDependency('dependency_name');
    }

    public function test_tracking_dependencies_when_dependency_is_ignored()
    {
        $telemetryMock = $this->createTelemetryClientMock();

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault(),
            new FailureCache($this->createMock(CacheInterface::class)),
            new NullLogger()
        );

        $client->configuration()->dependencies()->ignore('dependency_name');

        $telemetryMock->expects($this->never())
            ->method('trackDependency');

        $client->trackDependency('dependency_name');
    }

    public function test_tracking_exceptions_when_option_is_enabled()
    {
        $telemetryMock = $this->createTelemetryClientMock();

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault(),
            new FailureCache($this->createMock(CacheInterface::class)),
            new NullLogger()
        );

        $exception = new \Exception();

        $telemetryMock->expects($this->once())
            ->method('trackException')
            ->with($this->isInstanceOf(\Exception::class), null, null);

        $client->trackException($exception);
    }

    public function test_tracking_exceptions_when_option_is_disabled()
    {
        $telemetryMock = $this->createTelemetryClientMock();

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault(),
            new FailureCache($this->createMock(CacheInterface::class)),
            new NullLogger()
        );

        $client->configuration()->exceptions()->disable();

        $exception = new \Exception();

        $telemetryMock->expects($this->never())
            ->method('trackException');

        $client->trackException($exception);
    }

    public function test_tracking_exceptions_that_suppose_to_be_ignored()
    {
        $telemetryMock = $this->createTelemetryClientMock();

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault(),
            new FailureCache($this->createMock(CacheInterface::class)),
            new NullLogger()
        );

        $client->configuration()->exceptions()->ignore(\RuntimeException::class);

        $telemetryMock->expects($this->never())
            ->method('trackException')
            ->with($this->isInstanceOf(\RuntimeException::class), null, null);

        $client->trackException(new \RuntimeException());
    }

    public function test_tracking_traces_when_option_is_enabled()
    {
        $telemetryMock = $this->createTelemetryClientMock();

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault(),
            new FailureCache($this->createMock(CacheInterface::class)),
            new NullLogger()
        );

        $telemetryMock->expects($this->once())
            ->method('trackMessage')
            ->with('message', null, null);

        $client->trackMessage('message');
    }

    public function test_tracking_traces_when_option_is_disabled()
    {
        $telemetryMock = $this->createTelemetryClientMock();

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault(),
            new FailureCache($this->createMock(CacheInterface::class)),
            new NullLogger()
        );

        $client->configuration()->traces()->disable();

        $telemetryMock->expects($this->never())
            ->method('trackMessage');

        $client->trackMessage('message');
    }

    public function test_flushing_when_client_is_disabled()
    {
        $configuration = Configuration::createDefault();
        $configuration->disable();

        $telemetryMock = $this->createTelemetryClientMock();

        $telemetryMock->expects($this->never())->method('flush');

        $client = new Client(
            $telemetryMock,
            $configuration,
            new FailureCache($this->createMock(CacheInterface::class)),
            new NullLogger()
        );
        $client->flush();
    }

    public function test_fallback_logger_during_flush_unexpected_exception()
    {
        $telemetryMock = $this->createTelemetryClientMock();
        $loggerMock = $this->createMock(LoggerInterface::class);

        $telemetryMock->method('flush')->willThrowException(new \RuntimeException('Unexpected API exception'));

        $loggerMock->expects($this->once())
            ->method('error')
            ->with('Exception occurred while flushing App Insights Telemetry Client: Unexpected API exception');

        $client = new Client(
            $telemetryMock,
            Configuration::createDefault(),
            new FailureCache($this->createMock(CacheInterface::class)),
            $loggerMock
        );
        $client->flush();
    }

    public function test_adding_queue_to_failure_cache_on_unexpected_api_exception_and_cache_is_empty()
    {
        $telemetryMock = $this->createTelemetryClientMock();
        $loggerMock = $this->createMock(LoggerInterface::class);
        $cacheMock = $this->createMock(CacheInterface::class);

        $telemetryMock->method('flush')->willThrowException(new \RuntimeException('Unexpected API exception'));
        $cacheMock->method('has')->willReturn(false);

        $cacheMock->expects($this->once())
            ->method('set')
            ->with(FailureCache::CACHE_CHANNEL_KEY, serialize([new Envelope()]), FailureCache::CACHE_CHANNEL_TTL_SEC);

        $client = new Client($telemetryMock, Configuration::createDefault(), new FailureCache($cacheMock), $loggerMock);
        $client->flush();
    }

    public function test_adding_queue_to_failure_cache_on_unexpected_api_exception_and_cache_is_not_empty()
    {
        $telemetryMock = $this->createTelemetryClientMock();
        $loggerMock = $this->createMock(LoggerInterface::class);
        $cacheMock = $this->createMock(CacheInterface::class);

        $telemetryMock->method('flush')->willThrowException(new \RuntimeException('Unexpected API exception'));
        $cacheMock->method('has')->willReturn(true);
        $cacheMock->method('get')->willReturn(serialize([new Envelope()]));

        $cacheMock->expects($this->once())
            ->method('set')
            ->with(FailureCache::CACHE_CHANNEL_KEY, serialize([new Envelope(), new Envelope()]), FailureCache::CACHE_CHANNEL_TTL_SEC);

        $client = new Client($telemetryMock, Configuration::createDefault(), new FailureCache($cacheMock), $loggerMock);
        $client->flush();
    }

    /**
     * @dataProvider dataProvider
     * @param string|null $time
     * @param bool $sent
     */
    public function test_flush_with_provided_envelope_with_time_to_failure_cache(?string $time, bool $sent): void
    {
        $httpHandler = new GuzzleHttpHandler();
        $httpHandler->append(new Response());

        $httpClient = new GuzzleHttpClient([
            'handler' => $httpHandler,
        ]);

        $telemetryChannelMock = $this->createMock(Telemetry_Channel::class);
        $telemetryChannelMock->method('GetClient')->willReturn($httpClient);

        $telemetryClientMock = $this->createMock(Telemetry_Client::class);
        $telemetryClientMock->method('getChannel')->willReturn(
            $telemetryChannelMock
        );

        $envelope = new Envelope();
        $envelope->setTime($time);

        $failureCache = new FailureCache($fakeCache = new FakeCache());
        $failureCache->add($envelope);

        $client = new Client(
            $telemetryClientMock,
            Configuration::createDefault(),
            $failureCache,
            $this->createMock(LoggerInterface::class)
        );

        $client->flush();

        $requestSent = $httpHandler->getLastRequest() !== null;
        $this->assertSame($requestSent, $sent);
        $this->assertTrue($failureCache->empty());
    }

    public function dataProvider(): \Iterator
    {
        yield [null, false];
        yield [Utils::returnISOStringForTime(), true];
        yield [Utils::returnISOStringForTime((new \DateTimeImmutable('-1 day'))->getTimestamp()), true];
        yield [Utils::returnISOStringForTime((new \DateTimeImmutable('-10 day'))->getTimestamp()), false];
    }

    public function test_flush_when_cache_is_not_empty()
    {
        $telemetryMock = $this->createTelemetryClientMock();
        $loggerMock = $this->createMock(LoggerInterface::class);
        $cacheMock = $this->createMock(CacheInterface::class);

        $cacheMock->method('has')->willReturn(true);
        $cacheMock->method('get')->willReturn(serialize([new Envelope('some_older_entry')]));

        $cacheMock->expects($this->once())
            ->method('delete')
            ->with(FailureCache::CACHE_CHANNEL_KEY);

        $telemetryMock->expects($this->once())->method('flush');

        $client = new Client($telemetryMock, Configuration::createDefault(), new FailureCache($cacheMock), $loggerMock);
        $client->flush();
    }

    private function createTelemetryClientMock(): MockObject
    {
        $telemetryClientMock = $this->createMock(Telemetry_Client::class);
        $telemetryClientMock->method('getChannel')->willReturn(
            $telemetryChannelMock = $this->createMock(Telemetry_Channel::class)
        );
        $telemetryChannelMock->method('getQueue')->willReturn([new Envelope()]);
        $telemetryChannelMock->method('getSerializedQueue')->willReturn(json_encode([new Envelope()]));

        return $telemetryClientMock;
    }
}
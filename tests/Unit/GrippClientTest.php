<?php

namespace CodeBes\GrippSdk\Tests\Unit;

use CodeBes\GrippSdk\Exceptions\GrippException;
use CodeBes\GrippSdk\GrippClient;
use CodeBes\GrippSdk\Transport\JsonRpcClient;
use PHPUnit\Framework\TestCase;

class GrippClientTest extends TestCase
{
    protected function tearDown(): void
    {
        GrippClient::reset();
    }

    public function test_configure_stores_token_and_url(): void
    {
        GrippClient::configure('test-token', 'https://api.gripp.com');

        $this->assertEquals('test-token', GrippClient::getToken());
        $this->assertEquals('https://api.gripp.com', GrippClient::getBaseUrl());
    }

    public function test_configure_reads_from_env(): void
    {
        $_ENV['GRIPP_API_TOKEN'] = 'env-token';
        $_ENV['GRIPP_API_URL'] = 'https://env.gripp.com';

        GrippClient::configure();

        $this->assertEquals('env-token', GrippClient::getToken());
        $this->assertEquals('https://env.gripp.com', GrippClient::getBaseUrl());

        unset($_ENV['GRIPP_API_TOKEN'], $_ENV['GRIPP_API_URL']);
    }

    public function test_get_transport_throws_when_not_configured(): void
    {
        $this->expectException(GrippException::class);
        $this->expectExceptionMessage('GrippClient is not configured');

        GrippClient::getTransport();
    }

    public function test_get_transport_returns_json_rpc_client(): void
    {
        GrippClient::configure('token', 'https://api.gripp.com');

        $transport = GrippClient::getTransport();

        $this->assertInstanceOf(JsonRpcClient::class, $transport);
    }

    public function test_get_transport_returns_same_instance(): void
    {
        GrippClient::configure('token', 'https://api.gripp.com');

        $transport1 = GrippClient::getTransport();
        $transport2 = GrippClient::getTransport();

        $this->assertSame($transport1, $transport2);
    }

    public function test_set_transport_overrides_default(): void
    {
        GrippClient::configure('token', 'https://api.gripp.com');

        $custom = new JsonRpcClient('other-token', 'https://other.gripp.com');
        GrippClient::setTransport($custom);

        $this->assertSame($custom, GrippClient::getTransport());
    }

    public function test_reset_clears_configuration(): void
    {
        GrippClient::configure('token', 'https://api.gripp.com');
        GrippClient::reset();

        $this->assertNull(GrippClient::getToken());
        $this->assertNull(GrippClient::getBaseUrl());
    }

    public function test_reconfigure_resets_transport(): void
    {
        GrippClient::configure('token1', 'https://api1.gripp.com');
        $transport1 = GrippClient::getTransport();

        GrippClient::configure('token2', 'https://api2.gripp.com');
        $transport2 = GrippClient::getTransport();

        $this->assertNotSame($transport1, $transport2);
    }
}

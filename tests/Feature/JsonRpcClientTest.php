<?php

namespace CodeBes\GrippSdk\Tests\Feature;

use CodeBes\GrippSdk\Exceptions\AuthenticationException;
use CodeBes\GrippSdk\Exceptions\RateLimitException;
use CodeBes\GrippSdk\Exceptions\RequestException;
use CodeBes\GrippSdk\Transport\JsonRpcClient;
use CodeBes\GrippSdk\Transport\JsonRpcResponse;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JsonRpcClientTest extends TestCase
{
    protected array $history = [];

    protected function createClient(array $responses): JsonRpcClient
    {
        $this->history = [];
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($this->history));

        $httpClient = new HttpClient(['handler' => $handlerStack]);

        return new JsonRpcClient('test-token', 'https://api.gripp.com', $httpClient);
    }

    protected function batchResponse(array $items, int $status = 200, array $headers = []): Response
    {
        return new Response($status, $headers, json_encode($items));
    }

    protected function singleResponse(array $result, int $id = 1): Response
    {
        return $this->batchResponse([['id' => $id, 'result' => $result]]);
    }

    public function test_builds_correct_payload(): void
    {
        $client = $this->createClient([
            $this->singleResponse(['rows' => [], 'count' => 0]),
        ]);

        $client->call('company.get', [[], []]);

        $request = $this->history[0]['request'];
        $body = json_decode($request->getBody()->getContents(), true);

        // Request is always a batch array
        $this->assertIsArray($body);
        $this->assertArrayHasKey(0, $body);

        $payload = $body[0];
        $this->assertArrayNotHasKey('jsonrpc', $payload);
        $this->assertEquals('company.get', $payload['method']);
        $this->assertEquals([[], []], $payload['params']);
        $this->assertEquals(3011, $payload['apiconnectorversion']);
        $this->assertEquals(1, $payload['id']);
    }

    public function test_sends_bearer_token_header(): void
    {
        $client = $this->createClient([
            $this->singleResponse([]),
        ]);

        $client->call('task.get', []);

        $request = $this->history[0]['request'];
        $this->assertEquals('Bearer test-token', $request->getHeaderLine('Authorization'));
    }

    public function test_posts_to_correct_endpoint(): void
    {
        $client = $this->createClient([
            $this->singleResponse([]),
        ]);

        $client->call('task.get', []);

        $request = $this->history[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/public/api3.php', $request->getUri()->getPath());
    }

    public function test_response_wrapper_rows(): void
    {
        $response = new JsonRpcResponse([
            'id' => 1,
            'result' => [
                'rows' => [
                    ['id' => 1, 'name' => 'Test'],
                    ['id' => 2, 'name' => 'Test 2'],
                ],
                'count' => 2,
                'more_items_in_collection' => false,
            ],
        ]);

        $this->assertCount(2, $response->rows());
        $this->assertEquals(2, $response->count());
        $this->assertFalse($response->hasMoreItems());
    }

    public function test_response_wrapper_to_collection(): void
    {
        $response = new JsonRpcResponse([
            'id' => 1,
            'result' => [
                'rows' => [['id' => 1], ['id' => 2]],
                'count' => 2,
            ],
        ]);

        $collection = $response->toCollection();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $collection);
        $this->assertCount(2, $collection);
    }

    public function test_response_wrapper_record_id(): void
    {
        $response = new JsonRpcResponse([
            'id' => 1,
            'result' => ['id' => 42],
        ]);

        $this->assertEquals(42, $response->recordId());
    }

    public function test_response_wrapper_has_more_items(): void
    {
        $response = new JsonRpcResponse([
            'id' => 1,
            'result' => [
                'rows' => [['id' => 1]],
                'more_items_in_collection' => true,
            ],
        ]);

        $this->assertTrue($response->hasMoreItems());
    }

    public function test_response_error_detection(): void
    {
        $response = new JsonRpcResponse([
            'id' => 1,
            'error' => ['code' => -32600, 'message' => 'Invalid request'],
        ]);

        $this->assertTrue($response->hasError());
        $this->assertEquals(-32600, $response->error()['code']);
    }

    public function test_throws_authentication_exception_on_401(): void
    {
        $client = $this->createClient([
            new Response(401, [], json_encode(['error' => 'Unauthorized'])),
        ]);

        $this->expectException(AuthenticationException::class);
        $client->call('task.get', []);
    }

    public function test_throws_authentication_exception_on_403(): void
    {
        $client = $this->createClient([
            new Response(403, [], json_encode(['error' => 'Forbidden'])),
        ]);

        $this->expectException(AuthenticationException::class);
        $client->call('task.get', []);
    }

    public function test_throws_rate_limit_exception_on_429(): void
    {
        $client = $this->createClient([
            new Response(429, [
                'Retry-After' => '30',
                'X-RateLimit-Remaining' => '0',
            ], json_encode(['error' => 'Rate limited'])),
        ]);

        try {
            $client->call('task.get', []);
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertEquals(30, $e->getRetryAfter());
            $this->assertEquals(0, $e->getRemaining());
            $this->assertEquals(429, $e->getCode());
        }
    }

    public function test_retries_on_500_errors(): void
    {
        $client = $this->createClient([
            new Response(500, [], 'Server Error'),
            new Response(500, [], 'Server Error'),
            $this->singleResponse(['rows' => []]),
        ]);

        $response = $client->call('task.get', []);

        $this->assertCount(3, $this->history);
        $this->assertEmpty($response->rows());
    }

    public function test_throws_request_exception_on_json_rpc_error(): void
    {
        $client = $this->createClient([
            $this->batchResponse([
                ['id' => 1, 'error' => ['code' => -32600, 'message' => 'Invalid request']],
            ]),
        ]);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Invalid request');
        $client->call('bad.method', []);
    }

    public function test_throws_request_exception_on_string_error(): void
    {
        $client = $this->createClient([
            $this->batchResponse([
                ['id' => 1, 'error' => 'Insufficient rights.'],
            ]),
        ]);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Insufficient rights.');
        $client->call('department.get', []);
    }

    public function test_pagination_collects_all_pages(): void
    {
        $client = $this->createClient([
            $this->singleResponse([
                'rows' => [['id' => 1], ['id' => 2]],
                'count' => 2,
                'more_items_in_collection' => true,
            ]),
            $this->singleResponse([
                'rows' => [['id' => 3]],
                'count' => 1,
                'more_items_in_collection' => false,
            ], 2),
        ]);

        $response = $client->paginate('company.get', [[], []], 2);

        $this->assertCount(3, $response->rows());
        $this->assertFalse($response->hasMoreItems());
        $this->assertCount(2, $this->history);
    }

    public function test_pagination_sets_paging_options(): void
    {
        $client = $this->createClient([
            $this->singleResponse(['rows' => [], 'count' => 0, 'more_items_in_collection' => false]),
        ]);

        $client->paginate('task.get', [[], []], 50);

        $body = json_decode($this->history[0]['request']->getBody()->getContents(), true);
        $payload = $body[0];
        $this->assertEquals(0, $payload['params'][1]['paging']['firstresult']);
        $this->assertEquals(50, $payload['params'][1]['paging']['maxresults']);
    }

    public function test_request_counter_increments(): void
    {
        $client = $this->createClient([
            $this->singleResponse([]),
            $this->singleResponse([], 2),
        ]);

        $this->assertEquals(0, $client->getRequestCount());

        $client->call('task.get', []);
        $this->assertEquals(1, $client->getRequestCount());

        $client->call('task.get', []);
        $this->assertEquals(2, $client->getRequestCount());
    }

    public function test_batch_mode(): void
    {
        $client = $this->createClient([
            $this->batchResponse([
                ['id' => 1, 'result' => ['rows' => [['id' => 1]]]],
                ['id' => 2, 'result' => ['rows' => [['id' => 2]]]],
            ]),
        ]);

        $client->startBatch();
        $client->call('company.get', []);
        $client->call('task.get', []);
        $responses = $client->executeBatch();

        $this->assertCount(2, $responses);
        $this->assertCount(1, $this->history);
    }

    public function test_increments_request_id(): void
    {
        $client = $this->createClient([
            $this->singleResponse([]),
            $this->singleResponse([], 2),
        ]);

        $client->call('task.get', []);
        $client->call('company.get', []);

        $body1 = json_decode($this->history[0]['request']->getBody()->getContents(), true);
        $body2 = json_decode($this->history[1]['request']->getBody()->getContents(), true);

        $this->assertEquals(1, $body1[0]['id']);
        $this->assertEquals(2, $body2[0]['id']);
    }

    public function test_throws_on_status_page_served_during_an_outage(): void
    {
        // Trimmed from what api.gripp.com answered, with HTTP 200, on 22 October 2026.
        $client = $this->createClient([
            new Response(200, [], json_encode([
                'page' => ['id' => 'mr76vvnwb7ph', 'name' => 'Exact System', 'url' => 'https://status.exact.com'],
                'status' => ['indicator' => 'major', 'description' => 'Partial System Outage'],
                'components' => [['name' => 'Exact Gripp', 'status' => 'major_outage']],
            ])),
        ]);

        try {
            $client->call('task.get', []);
            $this->fail('Expected RequestException');
        } catch (RequestException $e) {
            $this->assertStringContainsString('not JSON-RPC', $e->getMessage());
            $this->assertStringContainsString('Exact System', $e->getMessage());
            $this->assertSame(200, $e->getCode());
            $this->assertSame('major', $e->getResponseData()['status']['indicator']);
        }
    }

    public function test_status_page_is_not_retried(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode(['status' => ['indicator' => 'major']])),
            $this->singleResponse(['rows' => [['id' => 1]]]),
        ]);

        try {
            $client->call('task.get', []);
            $this->fail('Expected RequestException');
        } catch (RequestException) {
            $this->assertCount(1, $this->history);
        }
    }

    public function test_pagination_throws_instead_of_returning_no_rows_on_a_status_page(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode(['status' => ['indicator' => 'major']])),
        ]);

        $this->expectException(RequestException::class);
        $client->paginate('task.get', [[], []]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonJsonRpcBodies(): array
    {
        return [
            'empty batch' => ['[]'],
            'json null' => ['null'],
            'json string' => ['"maintenance"'],
            'item without result or error' => ['[{"id": 1}]'],
            'second item without result' => ['[{"id": 1, "result": []}, {"id": 2}]'],
        ];
    }

    #[DataProvider('nonJsonRpcBodies')]
    public function test_throws_on_any_body_that_is_not_json_rpc(string $body): void
    {
        $client = $this->createClient([new Response(200, [], $body)]);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('not JSON-RPC');
        $client->call('task.get', []);
    }

    public function test_accepts_a_null_result(): void
    {
        $client = $this->createClient([
            $this->batchResponse([['id' => 1, 'result' => null]]),
        ]);

        $response = $client->call('task.delete', [1]);

        $this->assertNull($response->result());
    }

    public function test_accepts_a_non_batch_response(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode(['id' => 1, 'result' => ['rows' => [['id' => 7]]]])),
        ]);

        $this->assertSame([['id' => 7]], $client->call('task.get', [])->rows());
    }
}

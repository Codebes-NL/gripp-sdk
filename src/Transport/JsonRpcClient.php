<?php

namespace CodeBes\GrippSdk\Transport;

use CodeBes\GrippSdk\Exceptions\AuthenticationException;
use CodeBes\GrippSdk\Exceptions\RateLimitException;
use CodeBes\GrippSdk\Exceptions\RequestException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

class JsonRpcClient
{
    protected string $token;

    protected string $baseUrl;

    protected HttpClient $httpClient;

    protected int $requestCount = 0;

    protected int $idCounter = 0;

    protected array $batch = [];

    protected bool $batching = false;

    protected ?int $rateLimitRemaining = null;

    protected ?int $rateLimitLimit = null;

    /** @var callable|null */
    protected $beforeRequestCallback = null;

    /** @var callable|null */
    protected $onRateLimitCallback = null;

    protected const API_PATH = '/public/api3.php';

    protected const API_CONNECTOR_VERSION = 3011;

    protected const MAX_RETRIES = 3;

    public function __construct(string $token, string $baseUrl, ?HttpClient $httpClient = null)
    {
        $this->token = $token;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->httpClient = $httpClient ?? new HttpClient([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    public function call(string $method, array $params = []): JsonRpcResponse
    {
        $payload = $this->buildPayload($method, $params);

        if ($this->batching) {
            $this->batch[] = $payload;

            return new JsonRpcResponse(['id' => $payload['id'], 'result' => null]);
        }

        // Gripp API always expects a batch array, even for single calls
        $responseData = $this->sendRequest([$payload]);

        // Unwrap the first (only) response from the batch array
        if (isset($responseData[0])) {
            return new JsonRpcResponse($responseData[0]);
        }

        return new JsonRpcResponse($responseData);
    }

    public function startBatch(): void
    {
        $this->batching = true;
        $this->batch = [];
    }

    /**
     * @return JsonRpcResponse[]
     */
    public function executeBatch(): array
    {
        $this->batching = false;
        $batch = $this->batch;
        $this->batch = [];

        if (empty($batch)) {
            return [];
        }

        $responseData = $this->sendRequest($batch);
        $responses = [];

        if (isset($responseData[0])) {
            foreach ($responseData as $item) {
                $responses[] = new JsonRpcResponse($item);
            }
        } else {
            $responses[] = new JsonRpcResponse($responseData);
        }

        return $responses;
    }

    public function paginate(string $method, array $params = [], int $perPage = 200): JsonRpcResponse
    {
        $allRows = [];
        $offset = 0;

        // Ensure paging options are set
        if (! isset($params[1])) {
            $params[1] = [];
        }

        while (true) {
            $params[1]['paging'] = [
                'firstresult' => $offset,
                'maxresults' => $perPage,
            ];

            $response = $this->call($method, $params);

            $rows = $response->rows();
            $allRows = array_merge($allRows, $rows);

            if (! $response->hasMoreItems() || empty($rows)) {
                break;
            }

            $offset += $perPage;
        }

        return new JsonRpcResponse([
            'id' => $this->idCounter,
            'result' => [
                'rows' => $allRows,
                'count' => count($allRows),
                'more_items_in_collection' => false,
            ],
        ]);
    }

    public function getRequestCount(): int
    {
        return $this->requestCount;
    }

    /**
     * Register a callback that runs before each HTTP request.
     *
     * Receives (requestCount, rateLimitRemaining, rateLimitLimit).
     * Throw any exception to abort the request.
     *
     * @param  callable(int $requestCount, ?int $remaining, ?int $limit): void $callback
     */
    public function beforeRequest(callable $callback): void
    {
        $this->beforeRequestCallback = $callback;
    }

    /**
     * Register a callback that fires when a rate limit is hit (429 or 503/1004).
     *
     * Runs before the SDK throws RateLimitException.
     * If the callback throws, that exception propagates instead.
     * The $statusCode parameter distinguishes hourly limits (429) from burst throttles (503).
     *
     * @param  callable(?int $retryAfter, ?int $remaining, int $statusCode): void $callback
     */
    public function onRateLimitExceeded(callable $callback): void
    {
        $this->onRateLimitCallback = $callback;
    }

    /**
     * Get the remaining API calls for the current rate limit window,
     * as reported by the most recent response header.
     */
    public function getRateLimitRemaining(): ?int
    {
        return $this->rateLimitRemaining;
    }

    /**
     * Get the total API call limit for the current rate limit window,
     * as reported by the most recent response header.
     */
    public function getRateLimitLimit(): ?int
    {
        return $this->rateLimitLimit;
    }

    protected function buildPayload(string $method, array $params): array
    {
        return [
            'method' => $method,
            'params' => $params,
            'id' => ++$this->idCounter,
            'apiconnectorversion' => self::API_CONNECTOR_VERSION,
        ];
    }

    protected function sendRequest(array $payload, int $attempt = 1): array
    {
        if ($attempt === 1 && $this->beforeRequestCallback) {
            ($this->beforeRequestCallback)(
                $this->requestCount,
                $this->rateLimitRemaining,
                $this->rateLimitLimit,
            );
        }

        $url = $this->baseUrl . self::API_PATH;

        try {
            $response = $this->httpClient->post($url, [
                'json' => $payload,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $this->requestCount++;
            $this->trackRateLimitHeaders($response);
            $rawBody = $response->getBody()->getContents();
            $body = json_decode($rawBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RequestException('Invalid JSON response from API: ' . substr($rawBody, 0, 200));
            }

            // Check for JSON-RPC error in single-item batch response
            if (isset($body[0]['error'])) {
                $errorData = $body[0]['error'];
                throw $this->buildRequestException($errorData);
            }

            // Check for JSON-RPC error in non-batch response
            if (isset($body['error'])) {
                $errorData = $body['error'];
                throw $this->buildRequestException($errorData);
            }

            return $body;

        } catch (GuzzleRequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 0;

            if ($statusCode === 401 || $statusCode === 403) {
                throw new AuthenticationException(
                    'Authentication failed: ' . ($statusCode === 401 ? 'Invalid token' : 'Forbidden'),
                    $statusCode,
                    $e
                );
            }

            if ($statusCode === 429) {
                $retryAfter = $response->getHeaderLine('Retry-After') ?: null;
                $remaining = $response->getHeaderLine('X-RateLimit-Remaining') ?: null;

                if ($this->onRateLimitCallback) {
                    ($this->onRateLimitCallback)(
                        $retryAfter ? (int) $retryAfter : null,
                        $remaining ? (int) $remaining : null,
                        429,
                    );
                }

                throw new RateLimitException(
                    'Rate limit exceeded',
                    $retryAfter ? (int) $retryAfter : null,
                    $remaining ? (int) $remaining : null,
                    429,
                    $e
                );
            }

            // Gripp returns 503 with error_code 1004 for short-burst rate limits.
            // These are transient — retry with a brief backoff before giving up.
            if ($statusCode === 503) {
                // $response is guaranteed non-null here: $statusCode was derived from it above.
                $body = json_decode($response->getBody()->getContents(), true);
                $errorCode = $body[0]['error_code'] ?? $body['error_code'] ?? null;

                if ($errorCode === 1004) {
                    if ($attempt < self::MAX_RETRIES) {
                        sleep($attempt); // 1s, 2s backoff

                        return $this->sendRequest($payload, $attempt + 1);
                    }

                    if ($this->onRateLimitCallback) {
                        ($this->onRateLimitCallback)(null, null, 503);
                    }

                    throw new RateLimitException(
                        'Too many requests in a short period of time',
                        null,
                        null,
                        503,
                        $e
                    );
                }
            }

            // Retry on 5xx errors
            if ($statusCode >= 500 && $attempt < self::MAX_RETRIES) {
                return $this->sendRequest($payload, $attempt + 1);
            }

            throw new RequestException(
                'API request failed: ' . $e->getMessage(),
                null,
                $statusCode,
                $e
            );

        } catch (ConnectException $e) {
            if ($attempt < self::MAX_RETRIES) {
                return $this->sendRequest($payload, $attempt + 1);
            }

            throw new RequestException(
                'Connection failed: ' . $e->getMessage(),
                null,
                0,
                $e
            );
        }
    }

    protected function trackRateLimitHeaders($response): void
    {
        $remaining = $response->getHeaderLine('X-RateLimit-Remaining');
        $limit = $response->getHeaderLine('X-RateLimit-Limit');

        if ($remaining !== '') {
            $this->rateLimitRemaining = (int) $remaining;
        }

        if ($limit !== '') {
            $this->rateLimitLimit = (int) $limit;
        }
    }

    protected function buildRequestException(mixed $errorData): RequestException
    {
        if (is_array($errorData)) {
            return new RequestException(
                $errorData['message'] ?? 'Unknown API error',
                $errorData,
                $errorData['code'] ?? 0
            );
        }

        return new RequestException((string) $errorData);
    }
}

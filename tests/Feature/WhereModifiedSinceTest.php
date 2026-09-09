<?php

namespace CodeBes\GrippSdk\Tests\Feature;

use CodeBes\GrippSdk\GrippClient;
use CodeBes\GrippSdk\Resources\Hour;
use CodeBes\GrippSdk\Transport\JsonRpcClient;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * whereModifiedSince() has to ask two questions, because Gripp cannot answer one.
 *
 * Gripp leaves `updatedon` empty on records that were created and never edited,
 * and its filter array is ANDed with no OR available. Filtering on `updatedon`
 * alone therefore hides every untouched record - which is what made incremental
 * syncs silently lose draft hours for months.
 */
class WhereModifiedSinceTest extends TestCase
{
    protected array $history = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->history = [];
    }

    protected function tearDown(): void
    {
        GrippClient::reset();
        parent::tearDown();
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>  $pages  rows per HTTP call
     */
    protected function fakeTransport(array $pages): void
    {
        $responses = array_map(
            fn (array $rows) => new Response(200, [], json_encode([[
                'id' => 1,
                'result' => [
                    'rows' => $rows,
                    'count' => count($rows),
                    'start' => 0,
                    'more_items_in_collection' => false,
                ],
            ]])),
            $pages
        );

        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        GrippClient::setTransport(new JsonRpcClient(
            'test-token',
            'https://api.gripp.com',
            new HttpClient(['handler' => $stack])
        ));
    }

    /** All filters sent on the n-th HTTP call. */
    protected function filtersOfCall(int $index): array
    {
        $body = json_decode((string) $this->history[$index]['request']->getBody(), true);

        return $body[0]['params'][0];
    }

    public function test_it_queries_both_updatedon_and_createdon(): void
    {
        $this->fakeTransport([
            [['id' => 1, 'amount' => 8]],   // bewerkt sinds de peildatum
            [['id' => 2, 'amount' => 5]],   // aangemaakt sinds de peildatum, nooit bewerkt
        ]);

        $rows = Hour::where('employee', 42)
            ->whereModifiedSince(new \DateTimeImmutable('2026-09-01 00:00:00'))
            ->get();

        $this->assertCount(2, $this->history, 'Er horen twee queries te vertrekken, niet één.');

        $eerste = $this->filtersOfCall(0);
        $tweede = $this->filtersOfCall(1);

        // Het bestaande filter reist mee in beide helften.
        foreach ([$eerste, $tweede] as $filters) {
            $this->assertContains(
                ['field' => 'hour.employee', 'operator' => 'equals', 'value' => 42],
                $filters
            );
        }

        $this->assertContains(
            ['field' => 'hour.updatedon', 'operator' => 'greaterequals', 'value' => '2026-09-01 00:00:00'],
            $eerste
        );
        $this->assertContains(
            ['field' => 'hour.createdon', 'operator' => 'greaterequals', 'value' => '2026-09-01 00:00:00'],
            $tweede
        );

        $this->assertCount(2, $rows);
    }

    public function test_a_record_matching_both_halves_is_returned_once(): void
    {
        // Aangemaakt én daarna bewerkt: die komt in beide antwoorden terug.
        $this->fakeTransport([
            [['id' => 7, 'amount' => 3], ['id' => 8, 'amount' => 1]],
            [['id' => 7, 'amount' => 3]],
        ]);

        $rows = Hour::query()->whereModifiedSince(new \DateTimeImmutable('2026-09-01 00:00:00'))->get();

        $this->assertCount(2, $rows, 'Samenvoegen gebeurt op id, niet door aaneenplakken.');
        $this->assertEqualsCanonicalizing([7, 8], $rows->pluck('id')->all());
    }

    public function test_an_explicit_limit_applies_to_the_union(): void
    {
        $this->fakeTransport([
            [['id' => 1], ['id' => 2]],
            [['id' => 3], ['id' => 4]],
        ]);

        $rows = Hour::query()->whereModifiedSince(new \DateTimeImmutable('2026-09-01 00:00:00'))
            ->limit(3)
            ->get();

        $this->assertCount(3, $rows);
    }

    public function test_the_timestamp_fields_can_be_overridden(): void
    {
        $this->fakeTransport([[], []]);

        Hour::query()->whereModifiedSince(
            new \DateTimeImmutable('2026-09-01 00:00:00'),
            'hour.authorizedon',
            'hour.definitiveon'
        )->get();

        $this->assertContains(
            ['field' => 'hour.authorizedon', 'operator' => 'greaterequals', 'value' => '2026-09-01 00:00:00'],
            $this->filtersOfCall(0)
        );
        $this->assertContains(
            ['field' => 'hour.definitiveon', 'operator' => 'greaterequals', 'value' => '2026-09-01 00:00:00'],
            $this->filtersOfCall(1)
        );
    }

    public function test_a_query_without_it_still_makes_a_single_call(): void
    {
        $this->fakeTransport([[['id' => 1]]]);

        Hour::where('employee', 42)->get();

        $this->assertCount(1, $this->history);
    }
}

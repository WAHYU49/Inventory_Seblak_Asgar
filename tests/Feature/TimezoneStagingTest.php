<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\WibDateTimeFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use RuntimeException;
use Tests\TestCase;

class TimezoneStagingTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_business_date_forms_default_to_the_jakarta_calendar_date(): void
    {
        Date::setTestNow(CarbonImmutable::parse('2026-11-09 18:30:00', 'UTC'));
        $this->actingAs(new User(['name' => 'Timezone staging test']));

        $errors = new ViewErrorBag;
        $purchase = view('purchases.create', ['ingredients' => collect(), 'errors' => $errors])->render();
        $sale = view('sales.create', [
            'products' => collect([(object) ['id' => 1]]),
            'productData' => [],
            'errors' => $errors,
        ])->render();
        $adjustment = view('stock_adjustments.create', [
            'ingredients' => collect(),
            'errors' => $errors,
        ])->render();

        $this->assertStringContainsString('name="purchase_date"', $purchase);
        $this->assertStringContainsString('value="2026-11-10"', $purchase);
        $this->assertStringContainsString('name="sale_date"', $sale);
        $this->assertStringContainsString('value="2026-11-10"', $sale);
        $this->assertStringContainsString('name="adjustment_date"', $adjustment);
        $this->assertStringContainsString('value="2026-11-10"', $adjustment);
    }

    public function test_domain_models_store_utc_timestamps_and_keep_business_dates_unchanged(): void
    {
        $connection = $this->assertTestingDatabase();
        Date::setTestNow(CarbonImmutable::parse('2026-11-10 04:06:00', 'UTC'));

        $ingredient = Ingredient::create([
            'code' => 'TZ-'.Str::upper(Str::random(10)),
            'name' => 'Timezone staging fixture',
            'unit' => 'gram',
            'current_stock' => 2,
            'minimum_stock' => 1,
            'purchase_price' => 100,
            'is_active' => true,
        ]);
        $this->assertUtcTimestamp($connection, 'ingredients', $ingredient->id, '2026-11-10 04:06:00');
        $storedAt = $ingredient->created_at;
        $originalTimestamp = $storedAt->getTimestamp();
        $originalFormatted = $storedAt->format('Y-m-d H:i:sP');
        $this->assertSame('10-11-2026 11:06', WibDateTimeFormatter::format($storedAt));
        $this->assertSame('10-11-2026 11:06', WibDateTimeFormatter::format($storedAt));
        $this->assertSame($originalTimestamp, $storedAt->getTimestamp());
        $this->assertSame($originalFormatted, $storedAt->format('Y-m-d H:i:sP'));

        Date::setTestNow(CarbonImmutable::parse('2026-11-10 04:07:00', 'UTC'));
        $ingredient->update(['current_stock' => 3]);
        $ingredient->refresh();
        $this->assertSame('2026-11-10 04:06:00', $ingredient->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-10 04:07:00', $ingredient->updated_at->format('Y-m-d H:i:s'));

        $purchase = Purchase::create([
            'purchase_number' => 'TZ-PB-'.Str::upper(Str::random(10)),
            'purchase_date' => '2026-11-10',
            'total_amount' => 0,
        ]);
        $sale = Sale::create([
            'sale_number' => 'TZ-PJ-'.Str::upper(Str::random(10)),
            'sale_date' => '2026-11-10',
            'total_amount' => 0,
        ]);
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'TZ-SA-'.Str::upper(Str::random(10)),
            'ingredient_id' => $ingredient->id,
            'type' => 'increase',
            'quantity' => 1,
            'stock_before' => 3,
            'stock_after' => 4,
            'reason' => 'Timezone staging test',
            'adjustment_date' => '2026-11-10',
        ]);
        $movement = StockMovement::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'adjustment',
            'quantity' => 1,
            'stock_before' => 3,
            'stock_after' => 4,
            'reference_number' => $adjustment->adjustment_number,
        ]);

        foreach ([
            ['purchases', $purchase],
            ['sales', $sale],
            ['stock_adjustments', $adjustment],
            ['stock_movements', $movement],
        ] as [$table, $model]) {
            $this->assertUtcTimestamp($connection, $table, $model->id, '2026-11-10 04:07:00');
        }

        $this->assertSame('2026-11-10', $purchase->purchase_date->format('Y-m-d'));
        $this->assertSame('2026-11-10', $sale->sale_date->format('Y-m-d'));
        $this->assertSame('2026-11-10', $adjustment->adjustment_date->format('Y-m-d'));
    }

    public function test_database_queue_session_and_failed_jobs_use_only_the_testing_connection(): void
    {
        $connection = $this->assertTestingDatabase();
        Date::setTestNow(CarbonImmutable::parse('2026-11-10 04:06:00', 'UTC'));

        config([
            'queue.default' => 'database',
            'queue.connections.database.connection' => 'mysql_testing',
            'queue.failed.database' => 'mysql_testing',
            'session.driver' => 'database',
            'session.connection' => 'mysql_testing',
            'session.table' => 'sessions',
        ]);

        $queueMarker = (string) Str::uuid();
        Queue::connection('database')->push(new TimezoneStagingProbeJob($queueMarker));
        $this->assertSame(
            CarbonImmutable::parse('2026-11-10 04:06:00', 'UTC')->getTimestamp(),
            (int) $connection->table('jobs')
                ->where('payload', 'like', '%'.$queueMarker.'%')
                ->value('created_at')
        );

        $sessionId = Str::random(40);
        $session = app('session')->driver('database');
        $session->setId($sessionId);
        $session->start();
        $session->put('timezone_probe', 'testing');
        $session->save();
        $sessionPayload = $connection->table('sessions')->where('id', $sessionId)->value('payload');
        $this->assertNotEmpty($sessionPayload);
        $decodedSession = base64_decode($sessionPayload, true);
        $this->assertNotFalse($decodedSession);
        $this->assertStringContainsString('testing', $decodedSession);

        $payload = json_encode(['uuid' => (string) Str::uuid()], JSON_THROW_ON_ERROR);
        $failer = new DatabaseUuidFailedJobProvider(
            app('db'),
            'mysql_testing',
            'failed_jobs'
        );
        $failedUuid = $failer->log('database', 'default', $payload, new RuntimeException('Timezone staging probe'));
        $failedAt = $connection->table('failed_jobs')
            ->where('uuid', $failedUuid)
            ->value('failed_at');

        $this->assertSame('2026-11-10 04:06:00', $failedAt);
    }

    private function assertTestingDatabase(): Connection
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('mysql_testing', config('database.default'));
        $this->assertSame('inventory_seblak_asgar_test', config('database.connections.mysql_testing.database'));
        $this->assertSame('+00:00', config('database.connections.mysql_testing.timezone'));
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('UTC', date_default_timezone_get());

        $connection = DB::connection('mysql_testing');
        $this->assertSame(
            'inventory_seblak_asgar_test',
            $connection->selectOne('SELECT DATABASE() AS database_name')->database_name
        );
        $this->assertSame(
            '+00:00',
            $connection->selectOne('SELECT @@session.time_zone AS session_time_zone')->session_time_zone
        );

        return $connection;
    }

    private function assertUtcTimestamp(Connection $connection, string $table, int $id, string $expected): void
    {
        $this->assertSame(
            $expected,
            $connection->table($table)->where('id', $id)->value('created_at')
        );
    }
}

class TimezoneStagingProbeJob implements ShouldQueue
{
    public function __construct(public string $marker)
    {
    }

    public function handle(): void
    {
    }
}

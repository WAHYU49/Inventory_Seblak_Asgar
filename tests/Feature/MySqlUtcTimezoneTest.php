<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\WibDateTimeFormatter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MySqlUtcTimezoneTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mysql_timestamp_round_trip_is_utc_and_formats_as_wib(): void
    {
        $connection = DB::connection('mysql_testing');

        $this->assertSame(
            'inventory_seblak_asgar_test',
            $connection->selectOne('SELECT DATABASE() AS database_name')->database_name
        );
        $this->assertSame(
            '+00:00',
            $connection->selectOne('SELECT @@session.time_zone AS session_time_zone')->session_time_zone
        );

        $fixture = Carbon::parse('2026-11-10 04:06:00', 'UTC');
        $user = new User([
            'name' => 'UTC timezone fixture',
            'email' => Str::uuid().'@example.test',
            'password' => 'temporary-test-password',
        ]);
        $user->created_at = $fixture;
        $user->updated_at = $fixture;
        $user->save();

        $storedUser = User::query()->findOrFail($user->getKey());
        $storedTimestampBeforeFormatting = $storedUser->created_at->format('Y-m-d H:i:sP');
        $storedUnixTimestampBeforeFormatting = $storedUser->created_at->getTimestamp();

        $this->assertSame('2026-11-10 04:06:00', $storedUser->created_at->format('Y-m-d H:i:s'));
        $this->assertSame(
            '10-11-2026 11:06',
            WibDateTimeFormatter::format($storedUser->created_at)
        );
        $this->assertSame(
            '10-11-2026 11:06',
            WibDateTimeFormatter::format($storedUser->created_at)
        );
        $this->assertSame('2026-11-10 04:06:00+00:00', $fixture->format('Y-m-d H:i:sP'));
        $this->assertSame('UTC', $fixture->getTimezone()->getName());
        $this->assertSame($storedTimestampBeforeFormatting, $storedUser->created_at->format('Y-m-d H:i:sP'));
        $this->assertSame($storedUnixTimestampBeforeFormatting, $storedUser->created_at->getTimestamp());
    }
}

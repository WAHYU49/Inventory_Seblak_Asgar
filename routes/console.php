<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('testing:migrate', function () {
    $expectedDatabase = 'inventory_seblak_asgar_test';

    if (
        ! app()->environment('testing')
        || config('database.default') !== 'mysql_testing'
        || config('database.connections.mysql_testing.database') !== $expectedDatabase
    ) {
        $this->error('Refusing to migrate: the testing database configuration is not verified.');

        return 1;
    }

    $database = DB::connection('mysql_testing')
        ->selectOne('SELECT DATABASE() AS database_name')
        ->database_name;

    if ($database !== $expectedDatabase) {
        $this->error('Refusing to migrate: the active MySQL database is not the testing database.');

        return 1;
    }

    return $this->call('migrate', [
        '--database' => 'mysql_testing',
        '--force' => true,
    ]);
})->purpose('Safely migrate only the isolated MySQL testing database');

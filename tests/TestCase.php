<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if (
            ! $app->environment('testing')
            || $app['config']->get('database.default') !== 'mysql_testing'
            || $app['config']->get('database.connections.mysql_testing.database') !== 'inventory_seblak_asgar_test'
        ) {
            throw new RuntimeException('Tests may only use the inventory_seblak_asgar_test database.');
        }

        $database = $app['db']->connection('mysql_testing')
            ->selectOne('SELECT DATABASE() AS database_name')
            ->database_name;

        if ($database !== 'inventory_seblak_asgar_test') {
            throw new RuntimeException('The MySQL testing connection selected an unexpected database.');
        }

        return $app;
    }
}

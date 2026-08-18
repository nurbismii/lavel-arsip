<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DatabaseSafetyGuardTest extends TestCase
{
    use RefreshDatabase;

    private $refreshDatabaseAttempts = 0;

    public function test_unsafe_database_is_rejected_before_refresh_database_traits_run(): void
    {
        $this->assertSame(1, $this->refreshDatabaseAttempts);

        $originalDefault = config('database.default');
        $originalDatabase = config('database.connections.mysql.database');
        $exception = null;

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'unsafe_review_database',
        ]);

        try {
            $this->setUpTraits();
        } catch (RuntimeException $caught) {
            $exception = $caught;
        } finally {
            config([
                'database.default' => $originalDefault,
                'database.connections.mysql.database' => $originalDatabase,
            ]);
        }

        $this->assertSame(1, $this->refreshDatabaseAttempts, 'RefreshDatabase ran before the safety guard.');
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame('Automated tests must use SQLite :memory:.', $exception->getMessage());
    }

    protected function refreshInMemoryDatabase()
    {
        $this->refreshDatabaseAttempts++;
    }

    protected function refreshTestDatabase()
    {
        $this->refreshDatabaseAttempts++;
    }
}

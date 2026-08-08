<?php

namespace Tests;

use App\Models\AppSetting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Boot the application, then refuse to go any further unless the suite is
     * pointed at a disposable database.
     *
     * Laravel calls refreshApplication() from setUpTheTestEnvironment() *before*
     * setUpTraits(), so this runs before RefreshDatabase can migrate or drop
     * anything. That ordering is the whole point of hooking in here.
     *
     * Why this exists: a stale bootstrap/cache/config.php silently overrides the
     * <env> values in phpunit.xml. When that happened, the suite inherited the
     * live MySQL connection from .env and RefreshDatabase dropped every table in
     * the working database. A cached config is never correct for a test run, so
     * both conditions below abort instead of touching data.
     */
    protected function refreshApplication()
    {
        parent::refreshApplication();

        $this->guardAgainstNonDisposableDatabase();

        // Static memo would otherwise carry one test's settings into the next.
        AppSetting::flushMemo();
    }

    /**
     * Abort unless this run can only ever hit a throwaway SQLite database.
     */
    protected function guardAgainstNonDisposableDatabase(): void
    {
        if ($this->app->configurationIsCached()) {
            throw new RuntimeException(
                "Refusing to run tests while the configuration is cached.\n\n".
                "bootstrap/cache/config.php overrides the <env> values in phpunit.xml, so the\n".
                "suite would run against whatever database .env points at — and RefreshDatabase\n".
                "DROPS every table it finds.\n\n".
                "Fix it with:\n\n    php artisan config:clear\n"
            );
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            return;
        }

        $database = config("database.connections.{$connection}.database");

        throw new RuntimeException(
            "Refusing to run tests against the '{$connection}' connection.\n\n".
            "  driver:   {$driver}\n".
            "  database: {$database}\n\n".
            "These tests use RefreshDatabase, which DROPS every table in the target database.\n".
            "phpunit.xml is expected to select an in-memory SQLite database. Check phpunit.xml,\n".
            ".env.testing, and any DB_* variables exported in your shell before retrying.\n"
        );
    }
}

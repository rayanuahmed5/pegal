<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

// the command must be harmless to run twice and must refuse odd database names
class CreateDatabaseCommandTest extends TestCase
{
    public function test_it_is_fine_when_the_database_already_exists(): void
    {
        // the test database exists, tests/bootstrap.php creates it before the run
        $this->artisan('pegal:create-database')
            ->expectsOutputToContain('is ready')
            ->assertSuccessful();
    }

    public function test_it_refuses_a_database_name_that_is_not_plain(): void
    {
        Config::set('database.connections.mysql.database', 'pegal; DROP DATABASE x');

        $this->artisan('pegal:create-database')
            ->expectsOutputToContain('is not a plain name')
            ->assertFailed();
    }
}

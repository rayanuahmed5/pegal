<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use PDOException;

// creates the MySQL database named in .env if it is not there yet
// a script or a coding agent can't answer the "create it?" question that migrate asks, this one never asks
class CreateDatabase extends Command
{
    protected $signature = 'pegal:create-database';

    protected $description = 'Create the MySQL database from .env if it does not exist yet';

    public function handle(): int
    {
        $config = config('database.connections.'.config('database.default'));
        $name = (string) ($config['database'] ?? '');

        // the name goes into a SQL statement, so only plain names are accepted
        if (! preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            $this->error("The database name '{$name}' is not a plain name (letters, digits, underscore). Check DB_DATABASE in .env.");

            return self::FAILURE;
        }

        try {
            // connect to the server without choosing a database
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s', $config['host'], $config['port']),
                $config['username'],
                $config['password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            $this->error('Could not reach MySQL: '.$e->getMessage());
            $this->line('Is MySQL running, and do DB_HOST, DB_PORT, DB_USERNAME and DB_PASSWORD in .env match it?');

            return self::FAILURE;
        }

        $this->info("Database '{$name}' is ready.");

        return self::SUCCESS;
    }
}

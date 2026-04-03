<?php
declare(strict_types=1);

const DB_FILE = __DIR__ . '/week3-products.db';

function getPdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('sqlite:%s', DB_FILE);

    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Better SQLite behavior for concurrent writes and data consistency.
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA synchronous = NORMAL');

    initializeDatabase($pdo);

    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS Product (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_code TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            price NUMERIC NOT NULL DEFAULT 0 CHECK(price >= 0),
            quantity INTEGER NOT NULL DEFAULT 0 CHECK(quantity >= 0),
            description TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_product_name ON Product(name)');

    $pdo->exec(
        'CREATE TRIGGER IF NOT EXISTS trg_product_updated_at
        AFTER UPDATE ON Product
        FOR EACH ROW
        BEGIN
            UPDATE Product
            SET updated_at = CURRENT_TIMESTAMP
            WHERE id = OLD.id;
        END'
    );
}

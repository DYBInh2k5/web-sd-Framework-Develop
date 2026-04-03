-- SQLite schema for week 3 product management app
-- Use with: sqlite3 week3-products.db ".read week3-product-sqlite.sql"

CREATE TABLE IF NOT EXISTS Product (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    price NUMERIC NOT NULL DEFAULT 0 CHECK(price >= 0),
    quantity INTEGER NOT NULL DEFAULT 0 CHECK(quantity >= 0),
    description TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_product_name ON Product(name);

CREATE TRIGGER IF NOT EXISTS trg_product_updated_at
AFTER UPDATE ON Product
FOR EACH ROW
BEGIN
    UPDATE Product
    SET updated_at = CURRENT_TIMESTAMP
    WHERE id = OLD.id;
END;

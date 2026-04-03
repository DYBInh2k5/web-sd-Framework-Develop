# PHP Product Management

This repository now contains one unified product management app (CRUD) at the workspace root.

## Main files

- `db.php`: PDO connection for SQLite.
- `products.php`: Facade helpers (flash, CSRF, and service access).
- `src/Product.php`: Product model.
- `src/ProductRepository.php`: Data access layer.
- `src/ProductService.php`: Business logic layer.
- `product-list.php`: Product listing page.
- `product-add.php`: Add/edit product form.
- `product-delete.php`: Delete action.
- `styles.css`: Shared stylesheet for all pages.
- `week3-product-sqlite.sql`: SQLite schema.
- `week3-products.db`: SQLite database file.

## Optimizations included

- SQLite tuning with WAL mode, busy timeout, and safer prepare settings.
- Auto-initialize table/index/trigger in `db.php` on first run.
- Database constraints for non-negative `price` and `quantity`.
- Shared CSS file to remove duplicated inline styles.
- Search and pagination on product list.
- CSRF token validation for add/edit/delete requests.
- Debounced search input for smoother filtering.
- Limited pagination window for better navigation on large datasets.
- Return-to-list state preserved after add/edit/delete.

## Setup

1. Create database schema (from workspace root):

```bash
sqlite3 week3-products.db ".read week3-product-sqlite.sql"
```

2. Start PHP server:

```bash
php -S localhost:8000
```

3. Open app:

- `http://localhost:8000/product-list.php`

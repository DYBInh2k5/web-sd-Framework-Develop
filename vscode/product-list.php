<?php
declare(strict_types=1);

require_once __DIR__ . '/products.php';

$errorMessage = null;
$products = [];
$flashMessage = getFlashMessage();
$keyword = trim((string) ($_GET['q'] ?? ''));
$pageRaw = trim((string) ($_GET['page'] ?? '1'));
$page = ctype_digit($pageRaw) ? max(1, (int) $pageRaw) : 1;
$perPage = 8;
$totalPages = 1;
$currentPage = 1;
$totalItems = 0;

try {
    $result = getProductPage($keyword, $page, $perPage);
    $products = $result['items'];
    $totalPages = $result['totalPages'];
    $currentPage = $result['currentPage'];
    $totalItems = $result['totalItems'];
} catch (Throwable $exception) {
    $errorMessage = 'Database connection/query failed. Check db.php settings.';
}

$baseQuery = [];
if ($keyword !== '') {
    $baseQuery['q'] = $keyword;
}

function pageUrl(array $baseQuery, int $page): string
{
    $query = $baseQuery;
    $query['page'] = $page;
    return 'product-list.php?' . http_build_query($query);
}

function buildPaginationItems(int $currentPage, int $totalPages): array
{
    if ($totalPages <= 7) {
        return range(1, $totalPages);
    }

    $pages = [1, $totalPages];

    for ($p = $currentPage - 1; $p <= $currentPage + 1; $p++) {
        if ($p > 1 && $p < $totalPages) {
            $pages[] = $p;
        }
    }

    sort($pages);
    $pages = array_values(array_unique($pages));

    $items = [];
    $previous = null;
    foreach ($pages as $pageNumber) {
        if ($previous !== null && $pageNumber - $previous > 1) {
            $items[] = '...';
        }

        $items[] = $pageNumber;
        $previous = $pageNumber;
    }

    return $items;
}

$paginationItems = buildPaginationItems($currentPage, $totalPages);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container list-page">
    <div class="header">
        <h1>Product Management</h1>
        <a
            class="btn btn-add"
            href="product-add.php?<?= e(http_build_query(['back_q' => $keyword, 'back_page' => $currentPage])) ?>"
        >
            Add Product
        </a>
    </div>

    <form id="search-form" class="search-form" method="get" action="product-list.php">
        <input
            id="search-input"
            type="text"
            name="q"
            value="<?= e($keyword) ?>"
            placeholder="Search by code, name, or description"
            autocomplete="off"
        >
        <button class="btn btn-submit" type="submit">Search</button>
        <?php if ($keyword !== ''): ?>
            <a class="btn btn-back" href="product-list.php">Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($flashMessage !== null): ?>
        <div class="flash"><?= e($flashMessage) ?></div>
    <?php endif; ?>

    <?php if ($errorMessage === null): ?>
        <div class="summary">Total products: <?= e((string) $totalItems) ?></div>
    <?php endif; ?>

    <?php if ($errorMessage !== null): ?>
        <div class="error"><?= e($errorMessage) ?></div>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Name</th>
                <th>Price (VND)</th>
                <th>Quantity</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($products === []): ?>
                <tr>
                    <td colspan="7" class="empty">No product found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= e((string) $product->id) ?></td>
                        <td><?= e($product->productCode) ?></td>
                        <td><?= e($product->name) ?></td>
                        <td><?= number_format($product->price, 0, '.', ',') ?></td>
                        <td><?= e((string) $product->quantity) ?></td>
                        <td><?= e($product->description) ?></td>
                        <td>
                            <div class="actions">
                                <a
                                    class="btn btn-edit"
                                    href="product-add.php?<?= e(http_build_query(['id' => $product->id, 'back_q' => $keyword, 'back_page' => $currentPage])) ?>"
                                >
                                    Edit
                                </a>
                                <form
                                    class="inline-form"
                                    method="post"
                                    action="product-delete.php"
                                    onsubmit="return confirm('Delete this product?');"
                                >
                                    <input type="hidden" name="id" value="<?= e((string) $product->id) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                                    <input type="hidden" name="back_q" value="<?= e($keyword) ?>">
                                    <input type="hidden" name="back_page" value="<?= e((string) $currentPage) ?>">
                                    <button class="btn btn-delete" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a class="page-link" href="<?= e(pageUrl($baseQuery, $currentPage - 1)) ?>">Previous</a>
                <?php endif; ?>

                <?php foreach ($paginationItems as $item): ?>
                    <?php if ($item === '...'): ?>
                        <span class="page-ellipsis">...</span>
                    <?php else: ?>
                        <a class="page-link <?= $item === $currentPage ? 'active' : '' ?>" href="<?= e(pageUrl($baseQuery, (int) $item)) ?>">
                            <?= e((string) $item) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a class="page-link" href="<?= e(pageUrl($baseQuery, $currentPage + 1)) ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script>
    (function () {
        var form = document.getElementById('search-form');
        var input = document.getElementById('search-input');
        if (!form || !input) {
            return;
        }

        var timer = null;
        input.addEventListener('input', function () {
            if (timer !== null) {
                clearTimeout(timer);
            }

            timer = setTimeout(function () {
                form.requestSubmit();
            }, 450);
        });
    })();
</script>
</body>
</html>

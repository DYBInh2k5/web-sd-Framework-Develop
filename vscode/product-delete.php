<?php
declare(strict_types=1);

require_once __DIR__ . '/products.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$backKeyword = trim((string) ($_POST['back_q'] ?? ''));
$backPageRaw = trim((string) ($_POST['back_page'] ?? '1'));
$backPage = ctype_digit($backPageRaw) ? max(1, (int) $backPageRaw) : 1;

if ($method !== 'POST') {
    setFlashMessage('Invalid request method.');
    header('Location: ' . buildProductListUrl($backKeyword, $backPage));
    exit;
}

$csrfToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null;
if (!isValidCsrfToken($csrfToken)) {
    setFlashMessage('Invalid request token. Please retry.');
    header('Location: ' . buildProductListUrl($backKeyword, $backPage));
    exit;
}

$idRaw = isset($_POST['id']) ? trim((string) $_POST['id']) : '';
$id = $idRaw !== '' ? (int) $idRaw : 0;

if ($id <= 0) {
    setFlashMessage('Invalid product id.');
    header('Location: ' . buildProductListUrl($backKeyword, $backPage));
    exit;
}

try {
    if (deleteProduct($id)) {
        setFlashMessage('Product deleted successfully.');
    } else {
        setFlashMessage('Product not found.');
    }
} catch (Throwable $exception) {
    setFlashMessage('Delete failed. Check db.php settings.');
}

header('Location: ' . buildProductListUrl($backKeyword, $backPage));
exit;

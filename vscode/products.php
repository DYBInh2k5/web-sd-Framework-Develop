<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/src/ProductService.php';

const FLASH_MESSAGE_KEY = 'flash_message';
const CSRF_TOKEN_KEY = 'csrf_token';

function productService(): ProductService
{
    static $service = null;

    if ($service instanceof ProductService) {
        return $service;
    }

    $service = new ProductService(new ProductRepository(getPdo()));
    return $service;
}

function getProducts(): array
{
    $result = getProductPage('', 1, 1000);
    return $result['items'];
}

function findProductById(int $id): ?Product
{
    return productService()->findProductById($id);
}

function getProductPage(string $keyword, int $page, int $perPage): array
{
    return productService()->getProductPage($keyword, $page, $perPage);
}

function addProduct(array $input): array
{
    return productService()->addProduct($input);
}

function updateProduct(int $id, array $input): array
{
    return productService()->updateProduct($id, $input);
}

function deleteProduct(int $id): bool
{
    return productService()->deleteProduct($id);
}

function setFlashMessage(string $message): void
{
    $_SESSION[FLASH_MESSAGE_KEY] = $message;
}

function getFlashMessage(): ?string
{
    if (!isset($_SESSION[FLASH_MESSAGE_KEY])) {
        return null;
    }

    $message = (string) $_SESSION[FLASH_MESSAGE_KEY];
    unset($_SESSION[FLASH_MESSAGE_KEY]);

    return $message;
}

function getCsrfToken(): string
{
    if (!isset($_SESSION[CSRF_TOKEN_KEY]) || !is_string($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[CSRF_TOKEN_KEY];
}

function isValidCsrfToken(?string $token): bool
{
    if ($token === null || !isset($_SESSION[CSRF_TOKEN_KEY]) || !is_string($_SESSION[CSRF_TOKEN_KEY])) {
        return false;
    }

    return hash_equals($_SESSION[CSRF_TOKEN_KEY], $token);
}

function buildProductListUrl(string $keyword = '', int $page = 1): string
{
    $query = [];
    $safeKeyword = trim($keyword);
    $safePage = max(1, $page);

    if ($safeKeyword !== '') {
        $query['q'] = $safeKeyword;
    }

    if ($safePage > 1) {
        $query['page'] = $safePage;
    }

    if ($query === []) {
        return 'product-list.php';
    }

    return 'product-list.php?' . http_build_query($query);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

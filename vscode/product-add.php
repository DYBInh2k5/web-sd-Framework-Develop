<?php
declare(strict_types=1);

require_once __DIR__ . '/products.php';

$idRaw = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
$id = $idRaw !== '' ? (int) $idRaw : 0;
$isEdit = $id > 0;
$backKeyword = trim((string) ($_GET['back_q'] ?? ''));
$backPageRaw = trim((string) ($_GET['back_page'] ?? '1'));
$backPage = ctype_digit($backPageRaw) ? max(1, (int) $backPageRaw) : 1;

$editingProduct = null;
if ($isEdit) {
    try {
        $editingProduct = findProductById($id);
    } catch (Throwable $exception) {
        $editingProduct = null;
    }
}

if ($isEdit && $editingProduct === null) {
    setFlashMessage('Product not found.');
    header('Location: ' . buildProductListUrl($backKeyword, $backPage));
    exit;
}

$formData = [
    'name' => $editingProduct !== null ? $editingProduct->name : '',
    'price' => $editingProduct !== null ? (string) $editingProduct->price : '',
    'quantity' => $editingProduct !== null ? (string) $editingProduct->quantity : '',
    'description' => $editingProduct !== null ? $editingProduct->description : '',
];

$errors = [];
$dbError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null;
    $backKeyword = trim((string) ($_POST['back_q'] ?? ''));
    $backPageRaw = trim((string) ($_POST['back_page'] ?? '1'));
    $backPage = ctype_digit($backPageRaw) ? max(1, (int) $backPageRaw) : 1;

    $formData = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'price' => trim((string) ($_POST['price'] ?? '')),
        'quantity' => trim((string) ($_POST['quantity'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];

    if (!isValidCsrfToken($csrfToken)) {
        $errors['general'] = 'Invalid request token. Please refresh and try again.';
    }

    try {
        if ($errors === []) {
            if ($isEdit) {
                $errors = updateProduct($id, $formData);
                if ($errors === []) {
                    setFlashMessage('Product updated successfully.');
                    header('Location: ' . buildProductListUrl($backKeyword, $backPage));
                    exit;
                }
            } else {
                $errors = addProduct($formData);
                if ($errors === []) {
                    setFlashMessage('Product added successfully.');
                    header('Location: ' . buildProductListUrl($backKeyword, $backPage));
                    exit;
                }
            }
        }
    } catch (Throwable $exception) {
        $dbError = 'Cannot save product. Check db.php settings.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit Product' : 'Add Product' ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container form-page">
    <h1><?= $isEdit ? 'Edit Product' : 'Add Product' ?></h1>

    <?php if ($dbError !== null): ?>
        <div class="error-box"><?= e($dbError) ?></div>
    <?php endif; ?>

    <?php if (isset($errors['general'])): ?>
        <div class="error-box"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
        <input type="hidden" name="back_q" value="<?= e($backKeyword) ?>">
        <input type="hidden" name="back_page" value="<?= e((string) $backPage) ?>">

        <div class="row">
            <label for="name">Product Name</label>
            <input id="name" type="text" name="name" value="<?= e($formData['name']) ?>">
            <?php if (isset($errors['name'])): ?>
                <div class="field-error"><?= e($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="row">
            <label for="price">Price</label>
            <input id="price" type="number" step="0.01" min="0" name="price" value="<?= e($formData['price']) ?>">
            <?php if (isset($errors['price'])): ?>
                <div class="field-error"><?= e($errors['price']) ?></div>
            <?php endif; ?>
        </div>

        <div class="row">
            <label for="quantity">Quantity</label>
            <input id="quantity" type="number" min="0" name="quantity" value="<?= e($formData['quantity']) ?>">
            <?php if (isset($errors['quantity'])): ?>
                <div class="field-error"><?= e($errors['quantity']) ?></div>
            <?php endif; ?>
        </div>

        <div class="row">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= e($formData['description']) ?></textarea>
        </div>

        <div class="actions">
            <button class="btn btn-submit" type="submit"><?= $isEdit ? 'Update Product' : 'Create Product' ?></button>
            <a class="btn btn-back" href="<?= e(buildProductListUrl($backKeyword, $backPage)) ?>">Back to list</a>
        </div>
    </form>
</div>
</body>
</html>

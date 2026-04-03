<?php
declare(strict_types=1);

require_once __DIR__ . '/ProductRepository.php';

class ProductService
{
    public function __construct(private ProductRepository $repository)
    {
    }

    public function getProductPage(string $keyword, int $page, int $perPage): array
    {
        $safePerPage = max(1, $perPage);
        $totalItems = $this->repository->countByKeyword($keyword);
        $totalPages = max(1, (int) ceil($totalItems / $safePerPage));
        $currentPage = max(1, min($page, $totalPages));
        $offset = ($currentPage - 1) * $safePerPage;

        $items = $this->repository->findPaginatedByKeyword($keyword, $safePerPage, $offset);

        return [
            'items' => $items,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'perPage' => $safePerPage,
            'keyword' => trim($keyword),
        ];
    }

    public function findProductById(int $id): ?Product
    {
        return $this->repository->findById($id);
    }

    public function addProduct(array $input): array
    {
        [$data, $errors] = $this->validateProductInput($input);
        if ($errors !== []) {
            return $errors;
        }

        $this->repository->insert([
            'product_code' => $this->generateProductCode(),
            'name' => $data['name'],
            'price' => $data['price'],
            'quantity' => $data['quantity'],
            'description' => $data['description'],
        ]);

        return [];
    }

    public function updateProduct(int $id, array $input): array
    {
        [$data, $errors] = $this->validateProductInput($input);
        if ($errors !== []) {
            return $errors;
        }

        $affectedRows = $this->repository->updateById($id, $data);
        if ($affectedRows === 0 && !$this->repository->existsById($id)) {
            return ['general' => 'Product not found.'];
        }

        return [];
    }

    public function deleteProduct(int $id): bool
    {
        return $this->repository->deleteById($id);
    }

    private function generateProductCode(): string
    {
        do {
            $candidate = 'SP' . date('YmdHis') . random_int(100, 999);
        } while ($this->repository->existsByProductCode($candidate));

        return $candidate;
    }

    private function validateProductInput(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $priceRaw = trim((string) ($input['price'] ?? ''));
        $quantityRaw = trim((string) ($input['quantity'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Product name is required.';
        }

        if ($priceRaw === '' || !is_numeric($priceRaw) || (float) $priceRaw < 0) {
            $errors['price'] = 'Price must be a non-negative number.';
        }

        $validatedQuantity = filter_var($quantityRaw, FILTER_VALIDATE_INT);
        if ($quantityRaw === '' || $validatedQuantity === false || (int) $validatedQuantity < 0) {
            $errors['quantity'] = 'Quantity must be a non-negative integer.';
        }

        if ($errors !== []) {
            return [null, $errors];
        }

        return [[
            'name' => $name,
            'price' => (float) $priceRaw,
            'quantity' => (int) $validatedQuantity,
            'description' => $description,
        ], []];
    }
}

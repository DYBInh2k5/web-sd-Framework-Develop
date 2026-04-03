<?php
declare(strict_types=1);

require_once __DIR__ . '/Product.php';

class ProductRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?Product
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, product_code, name, price, quantity, description FROM Product WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->mapRowToProduct($row);
    }

    public function existsById(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM Product WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetchColumn() !== false;
    }

    public function existsByProductCode(string $productCode): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM Product WHERE product_code = :product_code LIMIT 1');
        $stmt->execute(['product_code' => $productCode]);

        return $stmt->fetchColumn() !== false;
    }

    public function countByKeyword(string $keyword): int
    {
        [$whereSql, $params] = $this->buildKeywordFilter($keyword);
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM Product ' . $whereSql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function findPaginatedByKeyword(string $keyword, int $limit, int $offset): array
    {
        [$whereSql, $params] = $this->buildKeywordFilter($keyword);
        $sql = 'SELECT id, product_code, name, price, quantity, description FROM Product '
            . $whereSql
            . ' ORDER BY id DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        return array_map(fn(array $row): Product => $this->mapRowToProduct($row), $rows);
    }

    public function insert(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO Product (product_code, name, price, quantity, description) VALUES (:product_code, :name, :price, :quantity, :description)'
        );

        $stmt->execute([
            'product_code' => $data['product_code'],
            'name' => $data['name'],
            'price' => $data['price'],
            'quantity' => $data['quantity'],
            'description' => $data['description'],
        ]);
    }

    public function updateById(int $id, array $data): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE Product SET name = :name, price = :price, quantity = :quantity, description = :description WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'price' => $data['price'],
            'quantity' => $data['quantity'],
            'description' => $data['description'],
        ]);

        return $stmt->rowCount();
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM Product WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function buildKeywordFilter(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return ['', []];
        }

        $whereSql = 'WHERE product_code LIKE :keyword OR name LIKE :keyword OR description LIKE :keyword';
        $params = [':keyword' => '%' . $keyword . '%'];

        return [$whereSql, $params];
    }

    private function mapRowToProduct(array $row): Product
    {
        return new Product(
            (int) $row['id'],
            (string) $row['product_code'],
            (string) $row['name'],
            (float) $row['price'],
            (int) $row['quantity'],
            (string) ($row['description'] ?? '')
        );
    }
}

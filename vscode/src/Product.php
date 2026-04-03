<?php
declare(strict_types=1);

class Product
{
    public int $id;
    public string $productCode;
    public string $name;
    public float $price;
    public int $quantity;
    public string $description;

    public function __construct(
        int $id,
        string $productCode,
        string $name,
        float $price,
        int $quantity,
        string $description = ''
    ) {
        $this->id = $id;
        $this->productCode = $productCode;
        $this->name = $name;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->description = $description;
    }
}

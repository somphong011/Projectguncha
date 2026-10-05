<?php
/**
 * CannabisOil Class (สินค้าประเภทน้ำมันสกัดกัญชา)
 * สืบทอดจาก: Product
 * ตัดสต็อกเป็นจำนวนขวด (int)
 * รับผิดชอบโดย: [คนที่ 1]
 */

require_once __DIR__ . '/Product.php';

class CannabisOil extends Product {
    private string $extractionMethod; // เช่น CO2 Extraction, Alcohol Extraction
    private int $stockBottles;         // สต็อกคงเหลือเป็นจำนวนขวด

    public function __construct(
        int $id,
        string $name,
        float $basePrice,
        string $extractionMethod,
        int $stockBottles
    ) {
        parent::__construct($id, $name, $basePrice, 'oil');
        $this->extractionMethod = $extractionMethod;
        $this->stockBottles = $stockBottles;
    }

    public function getExtractionMethod(): string {
        return $this->extractionMethod;
    }

    public function getStockBottles(): int {
        return $this->stockBottles;
    }

    public function getCurrentStock(): float {
        return (float)$this->stockBottles;
    }

    public function getUnit(): string {
        return 'ขวด';
    }

    /**
     * ตรวจสอบว่ามีจำนวนขวดเพียงพอหรือไม่
     */
    public function checkStock(float $quantity): bool {
        return $this->stockBottles >= (int)$quantity;
    }

    /**
     * ตัดสต็อกจำนวนขวด
     */
    public function reduceStock(float $quantity): void {
        $qty = (int)$quantity;
        if (!$this->checkStock($qty)) {
            throw new RuntimeException("สต็อกน้ำมันกัญชา {$this->name} ไม่เพียงพอ (คงเหลือ {$this->stockBottles} ขวด, ต้องการ {$qty} ขวด)");
        }
        $this->stockBottles -= $qty;
    }

    /**
     * ดึงรายละเอียดสินค้าน้ำมันกัญชา
     */
    public function getDetails(): array {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'product_type'      => 'oil',
            'base_price'        => $this->basePrice,
            'extraction_method' => $this->extractionMethod,
            'stock'             => $this->stockBottles,
            'unit'              => $this->getUnit(),
        ];
    }
}

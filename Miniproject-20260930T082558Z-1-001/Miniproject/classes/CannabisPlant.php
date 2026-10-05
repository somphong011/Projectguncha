<?php
/**
 * CannabisPlant Class (สินค้าประเภทต้นกล้ากัญชา)
 * สืบทอดจาก: Product
 * ตัดสต็อกเป็นจำนวนต้น (int)
 * รับผิดชอบโดย: [คนที่ 1]
 */

require_once __DIR__ . '/Product.php';

class CannabisPlant extends Product {
    private int $ageWeeks;     // อายุของต้นกล้า (สัปดาห์)
    private int $stockPieces;  // สต็อกคงเหลือเป็นจำนวนต้น

    public function __construct(
        int $id,
        string $name,
        float $basePrice,
        int $ageWeeks,
        int $stockPieces
    ) {
        parent::__construct($id, $name, $basePrice, 'plant');
        $this->ageWeeks = $ageWeeks;
        $this->stockPieces = $stockPieces;
    }

    public function getAgeWeeks(): int {
        return $this->ageWeeks;
    }

    public function getStockPieces(): int {
        return $this->stockPieces;
    }

    public function getCurrentStock(): float {
        return (float)$this->stockPieces;
    }

    public function getUnit(): string {
        return 'ต้น';
    }

    /**
     * ตรวจสอบว่ามีจำนวนต้นกล้าเพียงพอหรือไม่
     */
    public function checkStock(float $quantity): bool {
        return $this->stockPieces >= (int)$quantity;
    }

    /**
     * ตัดสต็อกจำนวนต้นกล้า
     */
    public function reduceStock(float $quantity): void {
        $qty = (int)$quantity;
        if (!$this->checkStock($qty)) {
            throw new RuntimeException("สต็อกต้นกล้ากัญชา {$this->name} ไม่เพียงพอ (คงเหลือ {$this->stockPieces} ต้น, ต้องการ {$qty} ต้น)");
        }
        $this->stockPieces -= $qty;
    }

    /**
     * ดึงรายละเอียดสินค้าต้นกล้า
     */
    public function getDetails(): array {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'product_type' => 'plant',
            'base_price'   => $this->basePrice,
            'age_weeks'    => $this->ageWeeks,
            'stock'        => $this->stockPieces,
            'unit'         => $this->getUnit(),
        ];
    }
}

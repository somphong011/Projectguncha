<?php
/**
 * Abstract Product Class
 * คลาสแม่สำหรับผลิตภัณฑ์ทุกประเภทในร้าน Dispensary
 * รับผิดชอบโดย: [คนที่ 1]
 */

abstract class Product {
    protected int $id;
    protected string $name;
    protected float $basePrice;
    protected string $productType; // 'flower', 'oil', 'plant'

    public function __construct(int $id, string $name, float $basePrice, string $productType) {
        $this->id = $id;
        $this->name = $name;
        $this->basePrice = $basePrice;
        $this->productType = $productType;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getBasePrice(): float {
        return $this->basePrice;
    }

    public function getProductType(): string {
        return $this->productType;
    }

    /**
     * ตรวจสอบว่ามีสต็อกเพียงพอต่อจำนวนที่ต้องการซื้อหรือไม่
     */
    abstract public function checkStock(float $quantity): bool;

    /**
     * ลดสต็อกสินค้าตามจำนวนที่ขาย
     */
    abstract public function reduceStock(float $quantity): void;

    /**
     * ดึงข้อมูลรายละเอียดของสินค้าในรูปแบบ Array
     */
    abstract public function getDetails(): array;

    /**
     * ดึงหน่วยนับของสินค้า (เช่น กรัม, ขวด, ต้น)
     */
    abstract public function getUnit(): string;

    /**
     * ดึงจำนวนสต็อกปัจจุบัน
     */
    abstract public function getCurrentStock(): float;

    /**
     * Helper สำหรับดึงสต็อกเป็นตัวเลข (ความเข้ากันได้กับ Admin/List)
     */
    public function getStockValue(): float {
        return $this->getCurrentStock();
    }

    /**
     * Helper สำหรับดึงประเภทสินค้า (ความเข้ากันได้กับ Product List)
     */
    public function getType(): string {
        return $this->productType;
    }

    /**
     * Helper สำหรับดึงหน่วยนับ
     */
    public function getStockUnit(): string {
        return $this->getUnit();
    }

    /**
     * แสดงผลจำนวนสต็อกพร้อมหน่วยนับ
     */
    public function getStockDisplay(): string {
        $val = $this->getCurrentStock();
        if ($this->productType === 'flower') {
            return number_format($val, 2) . ' ' . $this->getUnit();
        }
        return number_format($val, 0) . ' ' . $this->getUnit();
    }

    /**
     * Factory Method สร้าง Object ลูก (CannabisFlower, CannabisOil, CannabisPlant) ตามข้อมูลแถวใน DB
     */
    public static function createFromRow(array $row): Product {
        $type = $row['product_type'] ?? $row['type'] ?? 'flower';
        $price = (float)($row['base_price'] ?? 0);
        $name = $row['name'] ?? '';
        $id = (int)($row['id'] ?? 0);

        switch ($type) {
            case 'flower':
                $stock = (float)($row['stock_grams'] ?? $row['stock_quantity'] ?? 0.0);
                $strain = $row['strain_type'] ?? 'Hybrid';
                $thc = (float)($row['thc_percent'] ?? 20.0);
                return new CannabisFlower($id, $name, $price, $strain, $thc, $stock);

            case 'oil':
                $stock = (int)($row['stock_bottles'] ?? $row['stock_quantity'] ?? 0);
                $extract = $row['extraction_method'] ?? 'Supercritical CO2 Extraction';
                return new CannabisOil($id, $name, $price, $extract, $stock);

            case 'plant':
                $stock = (int)($row['stock_pieces'] ?? $row['stock_quantity'] ?? 0);
                $age = (int)($row['age_weeks'] ?? 4);
                return new CannabisPlant($id, $name, $price, $age, $stock);

            default:
                throw new InvalidArgumentException("ไม่พบประเภทสินค้า: " . $type);
        }
    }
}

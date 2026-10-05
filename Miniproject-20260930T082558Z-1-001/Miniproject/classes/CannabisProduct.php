<?php
/**
 * CannabisProduct Class (สอดคล้องกับ Class Diagram รูปที่ 1)
 * สืบทอดคุณสมบัติ (Inheritance) มาจาก Abstract class Product
 * และทำหน้าที่เป็นตัวแทนสินค้าประเภทกัญชาชั่งน้ำหนัก
 * 
 * [หลักการ OOP ที่ใช้]
 * 1. Inheritance: สืบทอด id, name, basePrice มาจาก Product
 * 2. Encapsulation: ซ่อน strainType, thcPercent, stockGrams เป็น private
 * 3. Polymorphism: Override เมธอด checkStock และ reduceStock เพื่อตัดสต็อกตามน้ำหนักทศนิยม (กรัม)
 */

require_once __DIR__ . '/Product.php';

class CannabisProduct extends Product {
    // แอตทริบิวต์ตาม Class Diagram รูปที่ 1
    private string $strainType; // สายพันธุ์ (เช่น Sativa, Indica, Hybrid)
    private float $thcPercent;  // เปอร์เซ็นต์สาร THC (%)
    private float $stockGrams;  // ปริมาณสต็อกคงเหลือเป็นกรัม (รองรับทศนิยม)

    public function __construct(
        int $id,
        string $name,
        float $basePrice,
        string $strainType,
        float $thcPercent,
        float $stockGrams
    ) {
        parent::__construct($id, $name, $basePrice, 'flower');
        $this->strainType = $strainType;
        $this->thcPercent = $thcPercent;
        $this->stockGrams = $stockGrams;
    }

    public function getStrainType(): string {
        return $this->strainType;
    }

    public function getThcPercent(): float {
        return $this->thcPercent;
    }

    public function getStockGrams(): float {
        return $this->stockGrams;
    }

    public function getCurrentStock(): float {
        return $this->stockGrams;
    }

    public function getUnit(): string {
        return 'กรัม (g)';
    }

    /**
     * +checkStock(float grams): bool ตาม Diagram รูปที่ 1
     * ตรวจสอบว่าปริมาณในสต็อกเพียงพอต่อความต้องการซื้อหรือไม่
     */
    public function checkStock(float $quantity): bool {
        return $this->stockGrams >= $quantity;
    }

    /**
     * +reduceStock(float grams) ตาม Diagram รูปที่ 1
     * ลดสต็อกตามน้ำหนักที่ขาย โดยปัดเศษทศนิยม 2 ตำแหน่ง
     */
    public function reduceStock(float $quantity): void {
        if (!$this->checkStock($quantity)) {
            throw new RuntimeException("สต็อกสินค้า {$this->name} ไม่เพียงพอ (คงเหลือ {$this->stockGrams}g, ต้องการ {$quantity}g)");
        }
        $this->stockGrams = round($this->stockGrams - $quantity, 2);
    }

    /**
     * +getDetails(): array ตาม Diagram รูปที่ 1
     * คืนค่าข้อมูลรายละเอียดสินค้าครบถ้วน
     */
    public function getDetails(): array {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'product_type' => 'flower',
            'base_price'   => $this->basePrice,
            'strain_type'  => $this->strainType,
            'thc_percent'  => $this->thcPercent,
            'stock'        => $this->stockGrams,
            'unit'         => $this->getUnit(),
        ];
    }
}

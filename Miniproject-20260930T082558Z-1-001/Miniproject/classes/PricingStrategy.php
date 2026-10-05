<?php
/**
 * PricingStrategy & BulkPricing (สอดคล้องกับ Class Diagram รูปที่ 1)
 * ประยุกต์ใช้ Strategy Design Pattern ในการคำนวณราคาสินค้าและส่วนลด
 * 
 * [หลักการ Design Pattern]
 * 1. Strategy Interface: กำหนดข้อตกลงร่วมกันในการคิดราคาผ่าน calculatePrice()
 * 2. RegularPricing (Concrete Strategy): คิดราคาปกติตามอัตราฐาน
 * 3. BulkPricing (Concrete Strategy): คิดราคาส่ง/ลดราคาตามระดับน้ำหนัก (Bulk Discount)
 * 4. Open-Closed Principle (OCP): สามารถเพิ่มโปรโมชั่นใหม่ๆ ได้โดยไม่ต้องแก้ไขคลาส Order
 */

/**
 * Interface สำหรับกำหนดกลยุทธ์การคำนวณราคา
 * ตาม Class Diagram: +calculatePrice(float weight, float basePrice) : float
 */
interface PricingStrategy {
    /**
     * คำนวณราคาสุทธิหลังหักส่วนลด
     *
     * @param float $weight ปริมาณหรือน้ำหนักสินค้าที่ซื้อ (กรัม หรือ หน่วยนับ)
     * @param float $basePrice ราคาต่อหน่วย (บาท)
     * @return float ราคาสุทธิที่ต้องชำระ (บาท)
     */
    public function calculatePrice(float $weight, float $basePrice): float;

    /**
     * ดึงเปอร์เซ็นต์ส่วนลดที่ได้รับ (%)
     */
    public function getDiscountPercent(float $weight): float;

    /**
     * ดึงมูลค่าส่วนลดที่เป็นจำนวนเงิน (บาท)
     */
    public function getDiscountAmount(float $weight, float $basePrice): float;

    /**
     * ดึงชื่อหรือคำอธิบายของกลยุทธ์ราคา
     */
    public function getStrategyName(): string;
}

/**
 * 1. RegularPricing (Implementation): คิดราคาปกติไม่มีส่วนลด
 */
class RegularPricing implements PricingStrategy {
    public function calculatePrice(float $weight, float $basePrice): float {
        return round($weight * $basePrice, 2);
    }

    public function getDiscountPercent(float $weight): float {
        return 0.0;
    }

    public function getDiscountAmount(float $weight, float $basePrice): float {
        return 0.0;
    }

    public function getStrategyName(): string {
        return 'ราคาปกติ (Regular Pricing)';
    }
}

/**
 * 2. BulkPricing (Implementation ตาม Diagram รูปที่ 1):
 * คำนวณราคาส่งตามระดับน้ำหนักที่ผู้จัดการร้านตั้งค่าไว้
 */
class BulkPricing implements PricingStrategy {
    // ระดับส่วนลดตามขั้นน้ำหนัก (จัดเรียงจากปริมาณมากไปน้อย)
    private array $tiers = [];

    /**
     * @param array $tiers รายการระดับส่วนลด เช่น:
     * [
     *   ['min_quantity' => 28.0, 'discount_percent' => 20.0],
     *   ['min_quantity' => 14.0, 'discount_percent' => 15.0],
     *   ['min_quantity' => 7.0,  'discount_percent' => 10.0],
     *   ['min_quantity' => 3.5,  'discount_percent' => 5.0]
     * ]
     */
    public function __construct(array $tiers = []) {
        if (!empty($tiers)) {
            $this->setTiers($tiers);
        } else {
            // ค่าเริ่มต้น (Default Tiers) ตามธรรมเนียมร้านกัญชาสากล (1/8 oz, 1/4 oz, 1/2 oz, 1 oz)
            $this->setTiers([
                ['min_quantity' => 28.0, 'discount_percent' => 20.0, 'desc' => 'ซื้อ 1 ออนซ์ (28g) ลด 20%'],
                ['min_quantity' => 14.0, 'discount_percent' => 15.0, 'desc' => 'ซื้อ 1/2 ออนซ์ (14g) ลด 15%'],
                ['min_quantity' => 7.0,  'discount_percent' => 10.0, 'desc' => 'ซื้อ 1/4 ออนซ์ (7g) ลด 10%'],
                ['min_quantity' => 3.5,  'discount_percent' => 5.0,  'desc' => 'ซื้อ 1/8 ออนซ์ (3.5g) ลด 5%'],
            ]);
        }
    }

    /**
     * ตั้งค่าระดับส่วนลด พร้อมจัดเรียงลำดับจากปริมาณมากไปหาน้อยอัตโนมัติ
     */
    public function setTiers(array $tiers): void {
        usort($tiers, function ($a, $b) {
            return $b['min_quantity'] <=> $a['min_quantity'];
        });
        $this->tiers = $tiers;
    }

    /**
     * โหลด Tiers จากฐานข้อมูล MySQL อัตโนมัติ (สำหรับแอดมินที่ปรับแก้ส่วนลดหลังบ้าน)
     */
    public static function createFromDatabase(PDO $pdo): self {
        try {
            $stmt = $pdo->query("SELECT min_quantity, discount_percent FROM bulk_pricing_tiers WHERE is_active = 1 ORDER BY min_quantity DESC");
            $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return new self($tiers);
        } catch (Exception $e) {
            return new self(); // หากยังไม่ต่อ DB ให้ใช้ Default Tiers
        }
    }

    /**
     * ค้นหาเปอร์เซ็นต์ส่วนลดที่ได้รับตามน้ำหนัก
     */
    public function getDiscountPercent(float $weight): float {
        foreach ($this->tiers as $tier) {
            if ($weight >= (float)$tier['min_quantity']) {
                return (float)$tier['discount_percent'];
            }
        }
        return 0.0;
    }

    /**
     * คำนวณมูลค่าส่วนลด (บาท)
     */
    public function getDiscountAmount(float $weight, float $basePrice): float {
        $percent = $this->getDiscountPercent($weight);
        $subtotal = $weight * $basePrice;
        return round($subtotal * ($percent / 100.0), 2);
    }

    /**
     * +calculatePrice(float weight, float basePrice) : float ตาม Diagram รูปที่ 1
     * คำนวณราคาสุทธิหลังหักส่วนลด
     */
    public function calculatePrice(float $weight, float $basePrice): float {
        $subtotal = $weight * $basePrice;
        $discount = $this->getDiscountAmount($weight, $basePrice);
        return round($subtotal - $discount, 2);
    }

    public function getStrategyName(): string {
        return 'โปรโมชั่นราคาส่ง (Bulk Pricing)';
    }

    public function getTiers(): array {
        return $this->tiers;
    }
}

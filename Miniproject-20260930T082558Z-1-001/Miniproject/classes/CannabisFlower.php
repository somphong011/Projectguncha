<?php
/**
 * CannabisFlower Class (สอดคล้องกับ Class Diagram รูปที่ 4 และรูปที่ 1)
 * สืบทอดคุณสมบัติ (Inheritance) มาจาก CannabisProduct และ Product
 * ทำหน้าที่เป็นสินค้าประเภทช่อดอกกัญชาที่จำหน่ายโดยการชั่งน้ำหนัก (กรัม)
 * 
 * [หลักการ OOP]
 * - Inheritance: ขยายความสามารถมาจาก CannabisProduct / Product
 * - Polymorphism: สามารถถูกจัดเก็บลงในตระกร้าของ Order ในฐานะ Product ได้อย่างยืดหยุ่น
 */

require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/CannabisProduct.php';

class CannabisFlower extends CannabisProduct {
    /**
     * คอนสตรัคเตอร์สำหรับดอกกัญชา
     * 
     * @param int $id รหัสสินค้า
     * @param string $name ชื่อสายพันธุ์สินค้า
     * @param float $basePrice ราคาต่อกรัม (บาท)
     * @param string $strainType ประเภทสายพันธุ์ (Sativa, Indica, Hybrid)
     * @param float $thcPercent ค่าสารเมาสำคัญ THC (%)
     * @param float $stockGrams ปริมาณน้ำหนักคงเหลือในสต็อก (กรัม)
     */
    public function __construct(
        int $id,
        string $name,
        float $basePrice,
        string $strainType,
        float $thcPercent,
        float $stockGrams
    ) {
        parent::__construct($id, $name, $basePrice, $strainType, $thcPercent, $stockGrams);
    }
}

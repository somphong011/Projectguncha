<?php
/**
 * ProductFactory Class (Factory Pattern)
 * ทำหน้าที่สร้าง Object สินค้า (CannabisFlower, CannabisOil, CannabisPlant)
 * จาก Database Row หรือข้อมูลที่ส่งเข้ามา
 * 
 * สอดคล้องกับหลักการ OOP Polymorphism & Factory Method
 */

require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/CannabisProduct.php';
require_once __DIR__ . '/CannabisFlower.php';
require_once __DIR__ . '/CannabisOil.php';
require_once __DIR__ . '/CannabisPlant.php';

class ProductFactory {
    /**
     * สร้าง Instance ของสินค้าจากข้อมูลแถวในฐานข้อมูล
     * รองรับทั้งคอลัมน์แบบ schema.sql และ database.sql
     *
     * @param array $row แถวข้อมูลจากตาราง products
     * @return Product|null
     */
    public static function createFromRow(array $row): ?Product {
        try {
            return Product::createFromRow($row);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * สร้างสินค้าตามประเภทที่กำหนด
     *
     * @param string $type flower | oil | plant
     * @param array $data ข้อมูลสำหรับคอนสตรัคเตอร์
     * @return Product
     */
    public static function create(string $type, array $data): Product {
        $id = (int)($data['id'] ?? 0);
        $name = (string)($data['name'] ?? '');
        $basePrice = (float)($data['base_price'] ?? $data['price'] ?? 0);

        switch (strtolower($type)) {
            case 'flower':
                $strain = (string)($data['strain_type'] ?? 'Hybrid');
                $thc = (float)($data['thc_percent'] ?? 20.0);
                $stock = (float)($data['stock_grams'] ?? $data['stock_quantity'] ?? 0.0);
                return new CannabisFlower($id, $name, $basePrice, $strain, $thc, $stock);

            case 'oil':
                $method = (string)($data['extraction_method'] ?? 'Supercritical CO2');
                $stock = (int)($data['stock_bottles'] ?? $data['stock_quantity'] ?? 0);
                return new CannabisOil($id, $name, $basePrice, $method, $stock);

            case 'plant':
                $age = (int)($data['age_weeks'] ?? 4);
                $stock = (int)($data['stock_pieces'] ?? $data['stock_quantity'] ?? 0);
                return new CannabisPlant($id, $name, $basePrice, $age, $stock);

            default:
                throw new InvalidArgumentException("ไม่พบประเภทสินค้า: {$type}");
        }
    }
}

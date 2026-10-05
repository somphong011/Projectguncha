<?php
/**
 * ========================================================================
 * UNIT TEST RUNNER: ตรวจสอบความถูกต้องของสถาปัตยกรรม OOP [ส่วนของคนที่ 1]
 * ========================================================================
 * ครอบคลุมการตรวจสอบตาม Class Diagram, Use Case, Flowchart และ Sequence Diagram
 * รันผ่าน Terminal: php test_runner.php
 * หรือเปิดผ่านเบราว์เซอร์: http://localhost/Miniproject/test_runner.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "========================================================\n";
echo "  UNIT TEST: ระบบ Dispensary POS [ส่วนของคนที่ 1]\n";
echo "  ตรวจสอบตาม Class, Use Case, Flowchart & Sequence Diagrams\n";
echo "========================================================\n\n";

// โหลดคลาสสถาปัตยกรรม OOP ทั้งหมด
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/CustomerGuard.php';
require_once __DIR__ . '/classes/Customer.php';
require_once __DIR__ . '/classes/Product.php';
require_once __DIR__ . '/classes/CannabisProduct.php';
require_once __DIR__ . '/classes/CannabisFlower.php';
require_once __DIR__ . '/classes/CannabisOil.php';
require_once __DIR__ . '/classes/CannabisPlant.php';
require_once __DIR__ . '/classes/PricingStrategy.php';
require_once __DIR__ . '/classes/Order.php';

$passed = 0;
$total = 0;

function assertTest(string $testName, bool $condition, string $detail = '') {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo "[PASS] {$testName}" . ($detail ? " ({$detail})" : "") . "\n";
    } else {
        echo "[FAIL] {$testName}" . ($detail ? " ({$detail})" : "") . "\n";
    }
}

// ----------------------------------------------------
// TEST 1: CustomerGuard (อายุ >= 20 ปีบริบูรณ์)
// ----------------------------------------------------
echo "--- ทดสอบ 1: CustomerGuard (Guard Pattern ตรวจสอบอายุ 20+) ---\n";
// ลูกค้าอายุ 25 ปี (ผ่าน)
$dob25 = date('Y-m-d', strtotime('-25 years'));
assertTest("ตรวจอายุ 25 ปี ต้องผ่าน (true)", CustomerGuard::verifyAge($dob25) === true);

// ลูกค้าอายุ 20 ปีพอดี (ผ่าน)
$dob20 = date('Y-m-d', strtotime('-20 years'));
assertTest("ตรวจอายุ 20 ปีพอดี ต้องผ่าน (true)", CustomerGuard::verifyAge($dob20) === true);

// ลูกค้าอายุ 17 ปี (ไม่ผ่าน บล็อกการขาย)
$dob17 = date('Y-m-d', strtotime('-17 years'));
assertTest("ตรวจอายุ 17 ปี ต้องไม่ผ่าน (false)", CustomerGuard::verifyAge($dob17) === false);

// ใส่วันที่ในอนาคต (ไม่ผ่าน)
$dobFuture = date('Y-m-d', strtotime('+1 year'));
assertTest("CustomerGuard: ใส่วันที่ในอนาคต ต้องไม่ผ่าน (false)", CustomerGuard::verifyAge($dobFuture) === false);

echo "\n";

// ----------------------------------------------------
// TEST 1.1: Customer Entity (ระบบลูกค้า: ชื่อ, อายุ, เพศ, อายุ 20+ ปี)
// ----------------------------------------------------
echo "--- ทดสอบ 1.1: Customer Entity (ระบบลูกค้า: ชื่อ, อายุ, เพศ, กฎหมาย 20+) ---\n";
$validCustomer = new Customer('สมชาย ใจดี', 25, 'ชาย');
assertTest("Customer: ตรวจสอบการเก็บชื่อ 'สมชาย ใจดี'", $validCustomer->getName() === 'สมชาย ใจดี');
assertTest("Customer: ตรวจสอบการเก็บอายุ 25 ปี", $validCustomer->getAge() === 25);
assertTest("Customer: ตรวจสอบการเก็บเพศ 'ชาย'", $validCustomer->getGender() === 'ชาย');
assertTest("Customer: อายุ 25 ปี canPurchase() ต้องเป็น true (ผ่านเกณฑ์)", $validCustomer->canPurchase() === true);
assertTest("CustomerGuard: ตรวจสอบผ่าน verifyCustomer(Customer)", CustomerGuard::verifyCustomer($validCustomer) === true);

$underageCustomer = new Customer('น้องนิด นามสมมุติ', 17, 'หญิง');
assertTest("Customer: อายุ 17 ปี canPurchase() ต้องเป็น false (ไม่ผ่าน)", $underageCustomer->canPurchase() === false);
assertTest("CustomerGuard: verifyCustomer(Customer 17 ปี) ต้องเป็น false", CustomerGuard::verifyCustomer($underageCustomer) === false);
$eligibility = $underageCustomer->getEligibilityStatus();
assertTest("Customer: ระบบแจ้งเตือนว่า 'ไม่สามารถซื้อได้' สำหรับอายุต่ำกว่า 20 ปี", str_contains($eligibility['message'], 'ไม่สามารถซื้อได้'));

echo "\n";

// ----------------------------------------------------
// TEST 2: Product Polymorphism (CannabisProduct, Flower, Oil, Plant)
// ----------------------------------------------------
echo "--- ทดสอบ 2: Product Polymorphism & Stock Reduction ---\n";
// ทดสอบ CannabisProduct ตาม Diagram รูปที่ 1
$cannabisProd = new CannabisProduct(101, 'Thai Stick', 250.00, 'Sativa', 18.0, 50.0);
assertTest("CannabisProduct: ตรวจสต็อก 5g ใน 50g", $cannabisProd->checkStock(5.0) === true);
$cannabisProd->reduceStock(5.0);
assertTest("CannabisProduct: ตัดสต็อก 5g เหลือ 45g", abs($cannabisProd->getCurrentStock() - 45.0) < 0.01);

// ทดสอบ CannabisFlower ตาม Diagram รูปที่ 4
$flower = new CannabisFlower(1, 'OG Kush', 450.00, 'Hybrid', 24.5, 100.50);
assertTest("CannabisFlower: ตรวจสต็อกทศนิยม 3.5g ที่มี 100.50g", $flower->checkStock(3.5) === true);
$flower->reduceStock(3.5);
assertTest("CannabisFlower: ตัดสต็อก 3.5g เหลือ 97g", abs($flower->getCurrentStock() - 97.00) < 0.01);

// ทดสอบ CannabisOil (นับขวด)
$oil = new CannabisOil(2, 'CBD 1000mg', 1200.00, 'CO2', 10);
assertTest("CannabisOil: ตรวจสต็อก 2 ขวด ที่มี 10 ขวด", $oil->checkStock(2) === true);
$oil->reduceStock(2);
assertTest("CannabisOil: ตัดสต็อก 2 ขวด เหลือ 8 ขวด", $oil->getCurrentStock() === 8.0);

// ทดสอบ CannabisPlant (นับต้น)
$plant = new CannabisPlant(3, 'White Runtz Clone', 650.00, 3, 5);
assertTest("CannabisPlant: ตรวจสต็อก 1 ต้น ที่มี 5 ต้น", $plant->checkStock(1) === true);
$plant->reduceStock(1);
assertTest("CannabisPlant: ตัดสต็อก 1 ต้น เหลือ 4 ต้น", $plant->getCurrentStock() === 4.0);

echo "\n";

// ----------------------------------------------------
// TEST 3: Pricing Strategy (Strategy Pattern)
// ----------------------------------------------------
echo "--- ทดสอบ 3: PricingStrategy (Regular vs Bulk Pricing) ---\n";
$regularStrategy = new RegularPricing();
$regPrice = $regularStrategy->calculatePrice(3.5, 450.00);
assertTest("RegularPricing: 3.5g @ 450 = 1575.00 (ไม่มีส่วนลด)", $regPrice === 1575.00);

$bulkStrategy = new BulkPricing();
// 3.5g ลด 5% (1575 - 78.75 = 1496.25)
$bulkPrice = $bulkStrategy->calculatePrice(3.5, 450.00);
$discountAmount = $bulkStrategy->getDiscountAmount(3.5, 450.00);
assertTest("BulkPricing: 3.5g ลด 5% (ประหยัด 78.75 บาท)", $discountAmount === 78.75);
assertTest("BulkPricing: ยอดสุทธิหลังลด = 1496.25", $bulkPrice === 1496.25);

// 14g ลด 15% (6300 - 945 = 5355.00)
$bulk14 = $bulkStrategy->calculatePrice(14.0, 450.00);
assertTest("BulkPricing: 14g ลด 15% (ยอดสุทธิ 5355.00)", $bulk14 === 5355.00);

echo "\n";

// ----------------------------------------------------
// TEST 4: Order Aggregation & Calculations
// ----------------------------------------------------
echo "--- ทดสอบ 4: Order Aggregation & Totals ---\n";
$order = new Order();
$flowerItem = new CannabisFlower(10, 'Sour Diesel', 500.00, 'Sativa', 26.0, 50.0);
$oilItem = new CannabisOil(20, 'Distillate Oil', 1000.00, 'Distillation', 5);

// เพิ่มดอกกัญชา 3.5g (ลดราคาส่ง 5% -> 3.5 x 500 = 1750, ลด 87.50, สุทธิ 1662.50)
$order->addItem($flowerItem, 3.5, $bulkStrategy);
// เพิ่มน้ำมัน 1 ขวด (ราคาปกติ 1000 บาท)
$order->addItem($oilItem, 1, $regularStrategy);

assertTest("Order: รวมสินค้าต่างชนิดในตะกร้าเดียว (2 ชนิด)", count($order->getItems()) === 2);
assertTest("Order: ราคารวมก่อนลด = 2750.00", $order->getTotalAmount() === 2750.00);
assertTest("Order: ส่วนลดรวม = 87.50", $order->getDiscountAmount() === 87.50);
assertTest("Order: ราคาสุทธิ = 2662.50", $order->getNetAmount() === 2662.50);

echo "\n";

// ----------------------------------------------------
// TEST 5: Sequence Diagram Flow & Checkout Simulation
// ----------------------------------------------------
echo "--- ทดสอบ 5: Sequence Diagram Flow (Checkout Verification) ---\n";
// ทดสอบกรณีลูกค้าอายุต่ำกว่า 20 ปี ต้องปฏิเสธการ Checkout
$failOrder = new Order();
$failOrder->addItem($flowerItem, 1.0, $regularStrategy);
$failResult = $failOrder->checkout($dob17);
assertTest("Sequence Check: ลูกค้าอายุ 17 ปี ต้อง Checkout ไม่สำเร็จ", $failResult['success'] === false);

// ทดสอบกรณีลูกค้าอายุผ่านเกณฑ์ (>= 20 ปี)
$passOrder = new Order();
$passOrder->addItem($flowerItem, 3.5, $bulkStrategy);
$passResult = $passOrder->checkout($dob25);
assertTest("Sequence Check: ลูกค้าอายุ 25 ปี Checkout สำเร็จ", $passResult['success'] === true);
assertTest("Sequence Check: ได้รหัสบิลใบเสร็จขึ้นต้นด้วย ORD-", str_starts_with($passResult['order_number'], 'ORD-'));

// ทดสอบกรณีส่งออบเจกต์ Customer (ผ่านเกณฑ์ 20+ ปี พร้อมข้อมูล ชื่อ, อายุ, เพศ)
$customerPassOrder = new Order();
$customerPassOrder->addItem($flowerItem, 3.5, $bulkStrategy);
$custPassResult = $customerPassOrder->checkout($validCustomer);
assertTest("Customer Checkout: ลูกค้าสมชาย (อายุ 25 ปี, ชาย) ทำรายการสำเร็จ", $custPassResult['success'] === true);
assertTest("Customer Checkout: บันทึกชื่อ 'สมชาย ใจดี' ถูกต้อง", $custPassResult['customer_name'] === 'สมชาย ใจดี');
assertTest("Customer Checkout: บันทึกเพศ 'ชาย' ถูกต้อง", $custPassResult['customer_gender'] === 'ชาย');
assertTest("Customer Checkout: บันทึกอายุ 25 ปี ถูกต้อง", $custPassResult['customer_age'] === 25);

// ทดสอบกรณีส่งออบเจกต์ Customer (อายุ 17 ปี ไม่ถึง 20 ปี ต้องปฏิเสธและแจ้งว่าไม่สามารถซื้อได้)
$customerFailOrder = new Order();
$customerFailOrder->addItem($flowerItem, 1.0, $regularStrategy);
$custFailResult = $customerFailOrder->checkout($underageCustomer);
assertTest("Customer Checkout: ลูกค้าอายุ 17 ปี ต้องไม่สำเร็จ (false)", $custFailResult['success'] === false);
assertTest("Customer Checkout: มีข้อความแจ้งเตือน 'ไม่สามารถซื้อได้'", str_contains($custFailResult['error'], 'ไม่สามารถซื้อได้'));

echo "\n";

// ----------------------------------------------------
// TEST 6: User & Role Permissions
// ----------------------------------------------------
echo "--- ทดสอบ 6: User Roles (Use Case Diagram: Staff vs Admin) ---\n";
$staffUser = new Staff(1, 'staff1', 'สมชาย หน้าร้าน');
assertTest("Staff: canManageStock ต้องเป็น false", $staffUser->canManageStock() === false);
assertTest("Staff: canCreateOrder ต้องเป็น true", $staffUser->canCreateOrder() === true);

$adminUser = new Admin(2, 'admin1', 'สมศักดิ์ ผู้จัดการ');
assertTest("Admin: canManageStock ต้องเป็น true", $adminUser->canManageStock() === true);
assertTest("Admin: canViewReports ต้องเป็น true", $adminUser->canViewReports() === true);

echo "\n========================================================\n";
echo "  สรุปผลการทดสอบ: ผ่าน {$passed} / {$total} การทดสอบ (" . round(($passed/$total)*100, 1) . "%)\n";
echo "========================================================\n";

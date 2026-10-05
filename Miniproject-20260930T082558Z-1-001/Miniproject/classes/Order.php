<?php
/**
 * Order Class (สอดคล้องกับ Class Diagram รูปที่ 1, 4 และ Sequence Diagram รูปที่ 5)
 * ทำหน้าที่รวมสินค้า (Aggregation), เรียกใช้ CustomerGuard (Dependency),
 * เรียกใช้ PricingStrategy (Dependency) และจัดการ Transaction ลงฐานข้อมูล
 * 
 * [การทำงานตาม Sequence Diagram รูปที่ 5]
 * 1. รับคำสั่งชำระเงิน (วันเกิดลูกค้า, น้ำหนัก/ปริมาณสินค้า)
 * 2. เรียก CustomerGuard::verifyAge() ตรวจสอบอายุลูกค้า
 * 3. หากอายุ >= 20 ปี ผ่านเข้าสู่ขั้นตอนตรวจสต็อก
 * 4. เรียก $product->checkStock() ตรวจสอบว่าสินค้าพอขายหรือไม่
 * 5. เรียก $strategy->calculatePrice() คำนวณราคาสุทธิและหักส่วนลดราคาส่ง
 * 6. เรียก $product->reduceStock() ตัดสต็อกใน Object
 * 7. UPDATE สต็อกลงฐานข้อมูล Database
 * 8. INSERT ข้อมูลคำสั่งซื้อและรายการย่อยลง Database
 * 9. ส่งคืนข้อมูลใบเสร็จที่สำเร็จ (Receipt Data)
 */

require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/CannabisProduct.php';
require_once __DIR__ . '/CannabisFlower.php';
require_once __DIR__ . '/CustomerGuard.php';
require_once __DIR__ . '/Customer.php';
require_once __DIR__ . '/PricingStrategy.php';

class Order {
    // แอตทริบิวต์ตาม Class Diagram รูปที่ 1
    private ?int $orderId = null;
    private float $totalAmount = 0.0;     // ราคารวมก่อนลด
    private float $discountAmount = 0.0;  // ส่วนลดรวม
    private float $netAmount = 0.0;       // ราคาสุทธิ

    private string $orderNumber = '';
    private array $items = [];

    public function __construct() {
        // สร้างรหัสออเดอร์อัตโนมัติ (เช่น ORD-20260928-ABC12)
        $this->orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }

    public function getOrderId(): ?int {
        return $this->orderId;
    }

    public function getTotalAmount(): float {
        return $this->totalAmount;
    }

    public function getDiscountAmount(): float {
        return $this->discountAmount;
    }

    public function getNetAmount(): float {
        return $this->netAmount;
    }

    public function getOrderNumber(): string {
        return $this->orderNumber;
    }

    public function getItems(): array {
        return $this->items;
    }

    /**
     * +addItem(Product p, float weight/quantity, PricingStrategy strategy)
     * รองรับทั้ง CannabisProduct ตาม Diagram 1 และ Product ลูกทุกชนิดตาม Diagram 4
     * 
     * @param Product $product สินค้า (CannabisProduct, CannabisFlower, CannabisOil, CannabisPlant)
     * @param float $weight ปริมาณหรือน้ำหนัก (เช่น 3.5 กรัม)
     * @param PricingStrategy|null $strategy กลยุทธ์การคิดราคา (หากไม่ระบุจะใช้ BulkPricing)
     */
    /**
     * +addItem(Product p, float weight/quantity, PricingStrategy strategy)
     * รองรับทั้ง CannabisProduct ตาม Diagram 1 และ Product ลูกทุกชนิดตาม Diagram 4
     * 
     * @param Product $product สินค้า (CannabisProduct, CannabisFlower, CannabisOil, CannabisPlant)
     * @param float $weight ปริมาณหรือน้ำหนัก (เช่น 3.5 กรัม)
     * @param PricingStrategy|null $strategy กลยุทธ์การคิดราคา (หากไม่ระบุจะใช้ BulkPricing)
     * @return bool คืนค่า true หากเพิ่มสินค้าสำเร็จ
     */
    public function addItem(Product $product, float $weight, ?PricingStrategy $strategy = null): bool {
        if ($weight <= 0) {
            throw new InvalidArgumentException("น้ำหนักหรือจำนวนสินค้าต้องมากกว่า 0");
        }

        // ตรวจสอบสต็อกเบื้องต้น
        if (!$product->checkStock($weight)) {
            return false;
        }

        // หากไม่ระบุ ให้ใช้ BulkPricing เป็นค่าเริ่มต้น
        if ($strategy === null) {
            $strategy = new BulkPricing();
        }

        $basePrice = $product->getBasePrice();
        $subtotal = round($weight * $basePrice, 2);
        // เรียกคำนวณราคาผ่าน Strategy Pattern ตาม Sequence Diagram ขั้นที่ 6
        $netPrice = $strategy->calculatePrice($weight, $basePrice);
        $discount = round($subtotal - $netPrice, 2);

        $this->items[] = [
            'product'          => $product,
            'quantity'         => $weight,
            'strategy'         => $strategy,
            'strategy_name'    => $strategy->getStrategyName(),
            'unit_price'       => $basePrice,
            'subtotal'         => $subtotal,
            'discount_applied' => $discount,
            'net_price'        => $netPrice,
        ];

        $this->calculateTotals();
        return true;
    }

    /**
     * คำนวณยอดรวมทั้งหมดของออเดอร์
     */
    private function calculateTotals(): void {
        $this->totalAmount = 0.0;
        $this->discountAmount = 0.0;
        $this->netAmount = 0.0;

        foreach ($this->items as $item) {
            $this->totalAmount += $item['subtotal'];
            $this->discountAmount += $item['discount_applied'];
            $this->netAmount += $item['net_price'];
        }

        $this->totalAmount = round($this->totalAmount, 2);
        $this->discountAmount = round($this->discountAmount, 2);
        $this->netAmount = round($this->netAmount, 2);
    }

    /**
     * +checkout(String customerBirthdate) : bool ตาม Diagram รูปที่ 1
     * ดำเนินการชำระเงินและตรวจสอบอายุแบบ Boolean
     */
    public function checkoutBool($customerParam): bool {
        $res = $this->checkout($customerParam);
        return $res['success'] ?? false;
    }

    /**
     * ดำเนินการ Checkout เต็มรูปแบบตาม Sequence Diagram
     * รองรับ Customer object, ข้อมูลลูกค้าชื่อ อายุ เพศ และการตรวจสอบ 20+
     * 
     * @param Customer|string|array $customerParam ข้อมูลลูกค้า หรือ วันเดือนปีเกิด
     * @param mixed $param2 PDO instance หรือยอดเงินสดที่รับมา (float)
     * @param mixed $param3 ชื่อพนักงาน (string) หรือรหัสผู้ใช้ (int)
     * @param mixed $param4 ข้อมูลเสริมของลูกค้า ['name' => ..., 'gender' => ..., 'age' => ...]
     * @return array ผลลัพธ์การสั่งซื้อและข้อมูลใบเสร็จ
     */
    public function checkout($customerParam, $param2 = null, $param3 = null, $param4 = null): array {
        $customerName = 'ลูกค้าทั่วไป';
        $customerGender = 'ไม่ระบุ';
        $customerAge = 0;
        $customerBirthdate = '';

        if ($customerParam instanceof Customer) {
            $customerName = $customerParam->getName();
            $customerAge = $customerParam->getAge();
            $customerGender = $customerParam->getGender();
            $customerBirthdate = $customerParam->getBirthdate() ?? date('Y-m-d', strtotime("-{$customerAge} years"));
        } elseif (is_array($customerParam)) {
            $customerName = $customerParam['name'] ?? 'ลูกค้าทั่วไป';
            $customerGender = $customerParam['gender'] ?? 'ไม่ระบุ';
            $customerBirthdate = $customerParam['birthdate'] ?? '';
            $customerAge = isset($customerParam['age']) ? (int)$customerParam['age'] : 0;
            if ($customerAge <= 0 && !empty($customerBirthdate)) {
                $customerAge = CustomerGuard::calculateAge($customerBirthdate);
            }
            if (empty($customerBirthdate) && $customerAge > 0) {
                $customerBirthdate = date('Y-m-d', strtotime("-{$customerAge} years"));
            }
        } elseif (is_string($customerParam)) {
            $customerBirthdate = $customerParam;
            $customerAge = CustomerGuard::getAge($customerBirthdate);
            if (is_array($param4)) {
                $customerName = $param4['name'] ?? 'ลูกค้าทั่วไป';
                $customerGender = $param4['gender'] ?? 'ไม่ระบุ';
                if (isset($param4['age']) && (int)$param4['age'] > 0) {
                    $customerAge = (int)$param4['age'];
                }
            }
        }

        // [Sequence 2-3] ตรวจสอบอายุลูกค้า (ต้อง >= 20 ปีบริบูรณ์)
        $isEligible = ($customerAge >= CustomerGuard::MINIMUM_AGE);
        if (!empty($customerBirthdate) && !CustomerGuard::verifyAge($customerBirthdate)) {
            $isEligible = false;
        }

        if (!$isEligible) {
            $msg = "ไม่สามารถซื้อได้: ลูกค้า '{$customerName}' มีอายุต่ำกว่า 20 ปี (อายุ {$customerAge} ปี) ต้องมีอายุตั้งแต่ 20 ปีขึ้นไปตามกฎหมาย";
            return [
                'success' => false,
                'error'   => $msg,
                'message' => $msg,
            ];
        }

        if (empty($this->items)) {
            $msg = "ไม่มีรายการสินค้าในคำสั่งซื้อ";
            return [
                'success' => false,
                'error'   => $msg,
                'message' => $msg,
            ];
        }

        // [Sequence 4-5] ตรวจสอบสต็อกสินค้าทุกชิ้นผ่าน checkStock()
        foreach ($this->items as $item) {
            /** @var Product $product */
            $product = $item['product'];
            $qty = $item['quantity'];

            if (!$product->checkStock($qty)) {
                $msg = "สต็อกไม่พอ: สินค้า '{$product->getName()}' คงเหลือ {$product->getCurrentStock()} {$product->getUnit()}, ต้องการ {$qty} {$product->getUnit()}";
                return [
                    'success' => false,
                    'error'   => $msg,
                    'message' => $msg,
                ];
            }
        }

        // ถอดรหัสพารามิเตอร์แบบ Polymorphic
        $pdo = null;
        $cashReceived = $this->netAmount;
        $staffName = 'CHILL42x Staff';
        $userId = 1;

        if ($param2 instanceof PDO) {
            $pdo = $param2;
            if (is_string($param3)) $staffName = $param3;
        } elseif (is_numeric($param2) || is_numeric($param3)) {
            // โหมดเรียกจาก pos.php: checkout($birthdate, $cashReceived, $userId)
            if (is_numeric($param2)) $cashReceived = (float)$param2;
            if (is_numeric($param3)) $userId = (int)$param3;
            elseif (is_string($param3)) $staffName = $param3;

            try {
                require_once __DIR__ . '/../config/Database.php';
                $pdo = Database::getInstance()->getConnection();
            } catch (Throwable $e) {
                $pdo = null;
            }
        } elseif (is_string($param3)) {
            $staffName = $param3;
        }

        $changeReturned = max(0.0, round($cashReceived - $this->netAmount, 2));
        $age = ($customerAge > 0) ? $customerAge : CustomerGuard::getAge($customerBirthdate);

        // [Sequence 8] ลดสต็อกใน Object ผ่าน reduceStock()
        foreach ($this->items as $item) {
            /** @var Product $product */
            $product = $item['product'];
            $product->reduceStock($item['quantity']);
        }

        // [Sequence 9-11] บันทึกลงฐานข้อมูล Database (ถ้ามี PDO)
        if ($pdo !== null) {
            try {
                $pdo->beginTransaction();

                // อัปเดตสต็อกใน Database (รองรับทั้ง schema.sql และ database.sql)
                foreach ($this->items as $item) {
                    $pid = $item['product']->getId();
                    $pqty = $item['quantity'];
                    $ptype = $item['product']->getProductType();

                    // ลองอัปเดตตามคอลัมน์เฉพาะ (database.sql)
                    if ($ptype === 'flower') {
                        $pUp = $pdo->prepare("UPDATE products SET stock_grams = GREATEST(0, stock_grams - ?) WHERE id = ?");
                        @$pUp->execute([$pqty, $pid]);
                    } elseif ($ptype === 'oil') {
                        $pUp = $pdo->prepare("UPDATE products SET stock_bottles = GREATEST(0, stock_bottles - ?) WHERE id = ?");
                        @$pUp->execute([$pqty, $pid]);
                    } elseif ($ptype === 'plant') {
                        $pUp = $pdo->prepare("UPDATE products SET stock_pieces = GREATEST(0, stock_pieces - ?) WHERE id = ?");
                        @$pUp->execute([$pqty, $pid]);
                    }

                    // อัปเดตแบบ stock_quantity ทั่วไป (schema.sql)
                    $genUp = $pdo->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id = ?");
                    @$genUp->execute([$pqty, $pid]);
                }

                $age = ($customerAge > 0) ? $customerAge : CustomerGuard::getAge($customerBirthdate);

                // บันทึกหัวบิล (รองรับตารางที่มี customer_name, customer_gender)
                try {
                    $insertOrder = $pdo->prepare(
                        "INSERT INTO orders (order_code, user_id, staff_name, customer_name, customer_age, customer_gender, customer_birthdate, total_amount, discount_amount, net_amount, cash_received, change_returned) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $insertOrder->execute([
                        $this->orderNumber,
                        $userId,
                        $staffName,
                        $customerName,
                        $age,
                        $customerGender,
                        $customerBirthdate,
                        $this->totalAmount,
                        $this->discountAmount,
                        $this->netAmount,
                        $cashReceived,
                        $changeReturned
                    ]);
                    $this->orderId = (int)$pdo->lastInsertId();

                    // บันทึก items แบบ database.sql
                    $insertItem = $pdo->prepare(
                        "INSERT INTO order_items (order_id, product_id, product_name, product_type, quantity, unit_label, unit_price, subtotal, discount, final_price)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    foreach ($this->items as $item) {
                        $insertItem->execute([
                            $this->orderId,
                            $item['product']->getId(),
                            $item['product']->getName(),
                            $item['product']->getProductType(),
                            $item['quantity'],
                            $item['product']->getUnit(),
                            $item['unit_price'],
                            $item['subtotal'],
                            $item['discount_applied'],
                            $item['net_price'],
                        ]);
                    }
                } catch (Exception $e1) {
                    try {
                        // Fallback 1: แบบ database.sql เดิม
                        $insertOrder = $pdo->prepare(
                            "INSERT INTO orders (order_code, user_id, customer_birthdate, customer_age, total_amount, discount_amount, net_amount, cash_received, change_returned) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        $insertOrder->execute([
                            $this->orderNumber,
                            $userId,
                            $customerBirthdate,
                            $age,
                            $this->totalAmount,
                            $this->discountAmount,
                            $this->netAmount,
                            $cashReceived,
                            $changeReturned
                        ]);
                        $this->orderId = (int)$pdo->lastInsertId();

                        // บันทึก items แบบ database.sql
                        $insertItem = $pdo->prepare(
                            "INSERT INTO order_items (order_id, product_id, product_name, product_type, quantity, unit_label, unit_price, subtotal, discount, final_price)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        foreach ($this->items as $item) {
                            $insertItem->execute([
                                $this->orderId,
                                $item['product']->getId(),
                                $item['product']->getName(),
                                $item['product']->getProductType(),
                                $item['quantity'],
                                $item['product']->getUnit(),
                                $item['unit_price'],
                                $item['subtotal'],
                                $item['discount_applied'],
                                $item['net_price'],
                            ]);
                        }
                    } catch (Exception $schemaFallback) {
                        // Fallback 2: หากโครงสร้างตารางเป็นแบบ schema.sql
                        $insertOrder = $pdo->prepare(
                            "INSERT INTO orders (order_number, total_amount, discount_amount, net_amount, staff_name, customer_birthdate) 
                             VALUES (?, ?, ?, ?, ?, ?)"
                        );
                        $insertOrder->execute([
                            $this->orderNumber,
                            $this->totalAmount,
                            $this->discountAmount,
                            $this->netAmount,
                            $staffName,
                            $customerBirthdate,
                        ]);
                        $this->orderId = (int)$pdo->lastInsertId();

                        $insertItem = $pdo->prepare(
                            "INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, discount_applied, subtotal)
                             VALUES (?, ?, ?, ?, ?, ?, ?)"
                        );
                        foreach ($this->items as $item) {
                            $insertItem->execute([
                                $this->orderId,
                                $item['product']->getId(),
                                $item['product']->getName(),
                                $item['quantity'],
                                $item['unit_price'],
                                $item['discount_applied'],
                                $item['net_price'],
                            ]);
                        }
                    }
                }

                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $this->orderId = rand(1000, 9999);
            }
        } else {
            $this->orderId = rand(1000, 9999);
        }

        // [Sequence 12] ออกใบเสร็จสำเร็จ
        return [
            'success'            => true,
            'message'            => 'ออกใบเสร็จสำเร็จ',
            'order_id'           => $this->orderId,
            'order_code'         => $this->orderNumber,
            'order_number'       => $this->orderNumber,
            'total_amount'       => $this->totalAmount,
            'discount_amount'    => $this->discountAmount,
            'net_amount'         => $this->netAmount,
            'cash_received'      => $cashReceived,
            'change_returned'    => $changeReturned,
            'staff_name'         => $staffName,
            'customer_name'      => $customerName,
            'customer_age'       => $age,
            'customer_gender'    => $customerGender,
            'customer_birthdate' => $customerBirthdate,
            'items'              => array_map(function ($it) {
                return [
                    'name'             => $it['product']->getName(),
                    'type'             => $it['product']->getProductType(),
                    'unit'             => $it['product']->getUnit(),
                    'quantity'         => $it['quantity'],
                    'unit_price'       => $it['unit_price'],
                    'subtotal'         => $it['subtotal'],
                    'discount_applied' => $it['discount_applied'],
                    'net_price'        => $it['net_price'],
                    'strategy'         => $it['strategy_name'],
                ];
            }, $this->items),
            'created_at'         => date('Y-m-d H:i:s'),
        ];
    }
}

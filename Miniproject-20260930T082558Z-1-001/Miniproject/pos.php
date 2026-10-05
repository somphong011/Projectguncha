<?php
/**
 * CHILL42x Dispensary POS - Point of Sale Terminal
 * สไตล์ Mostudio Dark Luxury Aesthetic & สถาปัตยกรรม OOP
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin('login.php');

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/ProductFactory.php';
require_once __DIR__ . '/classes/CustomerGuard.php';
require_once __DIR__ . '/classes/Customer.php';
require_once __DIR__ . '/classes/PricingStrategy.php';
require_once __DIR__ . '/classes/Order.php';

$db = Database::getInstance()->getConnection();
$currentUser = getCurrentUser();

$products = [];
$posError = '';
$posSuccess = '';

// ดึงสินค้าจากฐานข้อมูล
if ($db !== null) {
    try {
        $stmt = $db->query("SELECT p.*, IFNULL(p.type, p.product_type) as resolved_type,
            IFNULL(p.stock_grams, IFNULL(p.stock_bottles, IFNULL(p.stock_pieces, p.stock_quantity))) as current_stock_calc
            FROM products p 
            WHERE (p.type='flower' AND (p.stock_grams > 0 OR p.stock_quantity > 0))
               OR (p.type='oil' AND (p.stock_bottles > 0 OR p.stock_quantity > 0))
               OR (p.type='plant' AND (p.stock_pieces > 0 OR p.stock_quantity > 0))
               OR (p.product_type IS NOT NULL AND p.stock_quantity > 0)
            ORDER BY resolved_type, p.name");
        if ($stmt) {
            $products = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        $products = [];
    }
}

// Fallback Mock สินค้าพรีเมียมของ CHILL42x หากฐานข้อมูลยังไม่พร้อม
if (empty($products)) {
    $products = [
        [
            'id' => 1, 'name' => 'OG Kush Flower (Top Shelf)', 'type' => 'flower', 'product_type' => 'flower',
            'base_price' => 450.00, 'strain_type' => 'Hybrid (55% Indica / 45% Sativa)', 'thc_percent' => 24.50, 'stock_grams' => 150.50, 'stock_quantity' => 150.50,
            'image_url' => 'assets/images/flower_kush.jpg',
            'description' => 'ช่อดอกพรีเมียมเกรด Top Shelf เพาะปลูกระบบ Indoor ควบคุมสภาพแวดล้อม กลิ่นหอมสน เอิร์ธโทน และซิตรัสเข้มข้น อุดมด้วยเทอร์ปีน Myrcene และ Limonene ให้ความรู้สึกผ่อนคลายลึกพร้อมอารมณ์เบิกบาน'
        ],
        [
            'id' => 2, 'name' => 'Thai Sticky Sativa (หางกระรอกแท้)', 'type' => 'flower', 'product_type' => 'flower',
            'base_price' => 350.00, 'strain_type' => 'Sativa 100% (Landrace)', 'thc_percent' => 18.50, 'stock_grams' => 65.00, 'stock_quantity' => 65.00,
            'image_url' => 'assets/images/flower_thai_sativa.jpg',
            'description' => 'กัญชาสายพันธุ์แลนด์เรซแท้จากเทือกเขาภูพาน กลิ่นหอมสมุนไพรสดชื่น ผสานมะนาวป่า โดดเด่นด้วยเทอร์ปีน Terpinolene และ Pinene ออกฤทธิ์กระปรี้กระเปร่า เพิ่มสมาธิและความคิดสร้างสรรค์'
        ],
        [
            'id' => 3, 'name' => 'Granddaddy Purple (Indica)', 'type' => 'flower', 'product_type' => 'flower',
            'base_price' => 500.00, 'strain_type' => 'Indica (80% Indica / 20% Sativa)', 'thc_percent' => 22.00, 'stock_grams' => 45.00, 'stock_quantity' => 45.00,
            'image_url' => 'assets/images/flower_gdp.jpg',
            'description' => 'ช่อดอกสีม่วงเข้มปกคลุมด้วยไตรโคมหนาแน่น กลิ่นหอมหวานเด่นของเบอร์รี่ป่าและองุ่นสุก เทอร์ปีน Caryophyllene และ Linalool สูง ออกฤทธิ์คลายความตึงเครียดของกล้ามเนื้อ ช่วยให้นอนหลับลึกอย่างมีประสิทธิภาพ'
        ],
        [
            'id' => 4, 'name' => 'Full Spectrum CBD Oil Drops 1000mg', 'type' => 'oil', 'product_type' => 'oil',
            'base_price' => 1200.00, 'extraction_method' => 'Supercritical CO2 Extraction', 'stock_bottles' => 18, 'stock_quantity' => 18.00,
            'image_url' => 'assets/images/cannabis_oil.jpg',
            'description' => 'น้ำมันสกัดสายหยอดสูตร Full Spectrum เข้มข้น 1,000 มก. ในน้ำมัน MCT ออร์แกนิก สกัดเย็นด้วยก๊าซคาร์บอนไดออกไซด์แรงดันวิกฤต ไร้สารเคมีตกค้าง (THC < 0.2%) ช่วยบรรเทาอาการปวดและลดความวิตกกังวล'
        ],
        [
            'id' => 5, 'name' => 'Pure THC Distillate Drops (สายหยอด)', 'type' => 'oil', 'product_type' => 'oil',
            'base_price' => 1500.00, 'extraction_method' => 'Fractional Distillation', 'stock_bottles' => 10, 'stock_quantity' => 10.00,
            'image_url' => 'assets/images/oil_thc_distillate.jpg',
            'description' => 'สารสกัดหยดใต้ลิ้นความบริสุทธิ์สูง 85% ผ่านกระบวนการกลั่นลำดับส่วนมาตรฐานห้องปฏิบัติการ เสริมกลิ่นเทอร์ปีนธรรมชาติ ออกฤทธิ์รวดเร็วและควบคุมปริมาณหยดได้แม่นยำสำหรับการใช้งานเฉพาะจุด'
        ],
        [
            'id' => 6, 'name' => 'White Widow Clone (ต้นแม่พันธุ์)', 'type' => 'plant', 'product_type' => 'plant',
            'base_price' => 850.00, 'age_weeks' => 4, 'stock_pieces' => 15, 'stock_quantity' => 15.00,
            'image_url' => 'assets/images/cannabis_plant.jpg',
            'description' => 'ต้นกล้าตัดชำจากต้นแม่พันธุ์ White Widow แท้ อายุ 4 สัปดาห์ ระบบรากเดินเต็มก้อนร็อควูลพร้อมลงกระถางปลูก ลำต้นแข็งแรง ข้อถี่ ต้านทานโรคและศัตรูพืชได้ดีเยี่ยม'
        ],
        [
            'id' => 7, 'name' => 'Amnesia Haze Young Plant', 'type' => 'plant', 'product_type' => 'plant',
            'base_price' => 950.00, 'age_weeks' => 6, 'stock_pieces' => 8, 'stock_quantity' => 8.00,
            'image_url' => 'assets/images/plant_amnesia_haze.jpg',
            'description' => 'ต้นพันธุ์รุ่นเจริญเติบโต (Vegetative Stage) อายุ 6 สัปดาห์ ฟอร์มพุ่มกิ่งก้านสมบูรณ์ ลำต้นหนาพร้อมรับการตัดแต่งทรงพุ่ม (Topping/LST) ก่อนเข้าสู่ระยะทำดอก ให้ผลผลิตต่อกิ่งสูง'
        ]
    ];
}

// จัดการการส่งฟอร์มชำระเงิน (Checkout)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerAge = isset($_POST['customer_age']) ? (int)$_POST['customer_age'] : 0;
    $customerGender = trim($_POST['customer_gender'] ?? 'ไม่ระบุ');
    $birthdate = trim($_POST['customer_birthdate'] ?? '');
    $cashReceived = floatval($_POST['cash_received'] ?? 0);
    $cartItemsJson = $_POST['cart_items'] ?? '[]';
    $cartItems = json_decode($cartItemsJson, true);

    // หากไม่ได้ระบุอายุแต่ระบุวันเกิด ให้คำนวณอายุ
    if ($customerAge <= 0 && !empty($birthdate)) {
        $customerAge = CustomerGuard::calculateAge($birthdate);
    }
    // หากไม่ได้ระบุวันเกิดแต่มีอายุ ให้คำนวณวันเกิดย้อนหลัง
    if (empty($birthdate) && $customerAge > 0) {
        $birthdate = date('Y-m-d', strtotime("-{$customerAge} years"));
    }

    $customer = new Customer($customerName ?: 'ลูกค้าทั่วไป', $customerAge, $customerGender, $birthdate);

    if (empty($customerName)) {
        $posError = 'กรุณาระบุชื่อลูกค้า';
    } elseif ($customerAge <= 0) {
        $posError = 'กรุณาระบุอายุของลูกค้า';
    } elseif (empty($customerGender)) {
        $posError = 'กรุณาระบุเพศของลูกค้า';
    } elseif (!$customer->canPurchase()) {
        $posError = "❌ ไม่สามารถซื้อได้! ลูกค้าชื่อ '{$customerName}' มีอายุ {$customerAge} ปี ต้องมีอายุ 20 ปีขึ้นไปตาม พ.ร.บ. คุ้มครองและส่งเสริมภูมิปัญญาการแพทย์แผนไทย";
    } elseif (!empty($birthdate) && !CustomerGuard::verifyAge($birthdate)) {
        $calculatedAge = CustomerGuard::calculateAge($birthdate);
        $posError = "❌ ไม่สามารถซื้อได้! ลูกค้าชื่อ '{$customerName}' มีอายุ {$calculatedAge} ปี ต้องมีอายุ 20 ปีขึ้นไปตามกฎหมาย";
    } elseif (empty($cartItems)) {
        $posError = 'กรุณาเลือกสินค้าลงตะกร้าก่อนดำเนินการชำระเงิน';
    } else {
        // สร้าง Object Order และประมวลผล
        $order = new Order();
        $pricingStrategy = new BulkPricing();
        $hasError = false;

        foreach ($cartItems as $item) {
            $productId = (int)$item['id'];
            $qty = floatval($item['qty']);

            // หา product object จาก DB หรือ mockup
            $productObj = null;
            if ($db !== null) {
                try {
                    $pStmt = $db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
                    $pStmt->execute([':id' => $productId]);
                    $pRow = $pStmt->fetch();
                    if ($pRow) {
                        $productObj = ProductFactory::createFromRow($pRow);
                    }
                } catch (Exception $e) {
                    $productObj = null;
                }
            }

            if (!$productObj) {
                foreach ($products as $mock) {
                    if ((int)$mock['id'] === $productId) {
                        $productObj = ProductFactory::createFromRow($mock);
                        break;
                    }
                }
            }

            if ($productObj) {
                if (!$order->addItem($productObj, $qty, $pricingStrategy)) {
                    $posError = "สินค้า '{$productObj->getName()}' มีสต็อกไม่เพียงพอต่อการสั่งซื้อ";
                    $hasError = true;
                    break;
                }
            }
        }

        if (!$hasError) {
            $result = $order->checkout($customer, $cashReceived, $currentUser['id'], [
                'name'      => $customerName,
                'age'       => $customerAge,
                'gender'    => $customerGender,
                'birthdate' => $birthdate
            ]);
            if (!empty($result['success'])) {
                $result['customer_name'] = $customerName;
                $result['customer_age'] = $customerAge;
                $result['customer_gender'] = $customerGender;
                $result['customer_birthdate'] = $birthdate;
                $result['staff_name'] = $currentUser['full_name'] ?? 'พนักงานขาย CHILL42x';
                $_SESSION['last_receipt_order'] = $result;
                header("Location: receipt.php?order_id=" . $result['order_id']);
                exit();
            } else {
                $posError = $result['message'] ?? $result['error'] ?? 'เกิดข้อผิดพลาดในการเปิดบิล';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHILL42x POS - จุดขายหน้าร้าน</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .pos-layout {
            display: grid;
            grid-template-columns: 1fr 390px;
            gap: 24px;
            align-items: start;
            transition: all 0.3s ease;
        }
        body.cart-closed .pos-layout {
            grid-template-columns: 1fr 0px;
        }
        body.cart-closed .pos-cart-panel {
            display: none;
        }
        .catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 18px;
        }
        .pos-item-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: var(--transition-fast);
            cursor: pointer;
        }
        .pos-item-card:hover {
            border-color: var(--accent-gold);
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.6);
        }
        .item-thumb-box {
            position: relative;
            width: 100%;
            height: 140px;
            overflow: hidden;
            background: #000;
        }
        .item-thumb-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .pos-item-card:hover .item-thumb-box img {
            transform: scale(1.08);
        }
        .item-type-tag {
            position: absolute;
            top: 8px;
            left: 8px;
            z-index: 2;
        }
        .item-card-body {
            padding: 14px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }
        .item-card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .item-card-desc {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-bottom: 12px;
            line-height: 1.4;
        }
        .item-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--border-subtle);
            padding-top: 10px;
            margin-top: 4px;
        }
        .item-card-price {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-gold);
        }
        .cart-panel-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            position: sticky;
            top: 85px;
        }
        .cart-close-trigger {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.3rem;
            cursor: pointer;
            padding: 2px 6px;
            line-height: 1;
            border-radius: 4px;
            transition: var(--transition-fast);
        }
        .cart-close-trigger:hover {
            color: var(--status-danger);
            background: rgba(239, 68, 68, 0.1);
        }
        .cart-toggle-bar-btn {
            background: var(--bg-surface-subtle);
            border: 1px solid var(--border-color);
            color: var(--accent-gold);
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: none;
        }
        body.cart-closed .cart-toggle-bar-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .floating-mobile-cart-btn {
            display: none;
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--color-primary);
            color: #000;
            border: none;
            border-radius: var(--radius-full);
            padding: 12px 20px;
            font-weight: 700;
            font-size: 0.95rem;
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.5);
            z-index: 90;
            cursor: pointer;
        }
        @media (max-width: 991px) {
            .pos-layout {
                grid-template-columns: 1fr;
            }
            .pos-cart-panel {
                position: fixed;
                top: 0;
                right: -420px;
                width: 360px;
                max-width: 90vw;
                height: 100vh;
                z-index: 100;
                transition: right 0.3s ease;
                background: var(--bg-surface);
                overflow-y: auto;
            }
            body.cart-open .pos-cart-panel {
                right: 0;
            }
            .floating-mobile-cart-btn {
                display: flex;
                align-items: center;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="app-container">
        <!-- หัวเรื่องและปุ่มเปิด/ปิดตะกร้า -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    จุดขายหน้าร้าน CHILL42x POS
                </h1>
                <p class="page-subtitle">ชั่งน้ำหนักช่อดอก (กรัม), น้ำมันสกัดสายหยอด (Drops) คำนวณส่วนลด Bulk Pricing อัตโนมัติ</p>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <button type="button" class="cart-toggle-bar-btn" onclick="toggleCartDrawer()">
                    🛒 แสดงตะกร้าสินค้า (<span id="cartCountHeader">0</span>)
                </button>
                <div style="font-size: 0.88rem; color: var(--text-secondary); background: var(--bg-card); border: 1px solid var(--border-color); padding: 6px 14px; border-radius: var(--radius-sm);">
                    แคชเชียร์: <strong style="color: var(--accent-gold);"><?= htmlspecialchars($currentUser['full_name']) ?></strong>
                </div>
            </div>
        </div>

        <?php if (!empty($posError)): ?>
            <div class="alert alert-danger">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span><?= htmlspecialchars($posError) ?></span>
            </div>
        <?php endif; ?>

        <div class="pos-layout" id="posLayout">
            <!-- คอลัมน์ซ้าย: แคตตาล็อกสินค้าพร้อมรูปถ่าย Studio -->
            <div>
                <div class="card" style="margin-bottom: 20px; padding: 14px 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;">
                            🌿 เลือกรายการสินค้าใส่ตะกร้า (Click to Add)
                        </div>
                        <div class="filter-pills">
                            <button type="button" class="filter-pill active" onclick="filterCatalog('all', this)">ทั้งหมด (All)</button>
                            <button type="button" class="filter-pill" onclick="filterCatalog('flower', this)">🌸 ช่อดอก (Flowers)</button>
                            <button type="button" class="filter-pill" onclick="filterCatalog('oil', this)">💧 สายหยอด (Oil Drops)</button>
                            <button type="button" class="filter-pill" onclick="filterCatalog('plant', this)">🌱 ต้นกล้า (Clones)</button>
                        </div>
                    </div>
                </div>

                <div class="catalog-grid" id="catalogContainer">
                    <?php foreach ($products as $p): 
                        $pObj = ProductFactory::createFromRow($p);
                        if (!$pObj) continue;

                        $type = $pObj->getType();
                        $thumb = 'assets/images/flower_kush.jpg';
                        if ($type === 'oil') $thumb = 'assets/images/cannabis_oil.jpg';
                        elseif ($type === 'plant') $thumb = 'assets/images/cannabis_plant.jpg';
                        if (!empty($p['image_url']) && file_exists(__DIR__ . '/' . $p['image_url'])) {
                            $thumb = $p['image_url'];
                        }
                    ?>
                        <div class="pos-item-card catalog-card" data-type="<?= $type ?>" 
                             onclick="addToCart(<?= $pObj->getId() ?>, '<?= addslashes(htmlspecialchars($pObj->getName())) ?>', <?= $pObj->getBasePrice() ?>, '<?= $type ?>', '<?= $pObj->getStockUnit() ?>', <?= $pObj->getStockValue() ?>)">
                            <div class="item-thumb-box">
                                <img src="<?= $thumb ?>" alt="<?= htmlspecialchars($pObj->getName()) ?>" loading="lazy">
                                <div class="item-type-tag">
                                    <span class="badge badge-<?= $type ?>">
                                        <?= ($type === 'flower') ? '🌸 ช่อดอก' : (($type === 'oil') ? '💧 สายหยอด' : '🌱 ต้นพันธุ์') ?>
                                    </span>
                                </div>
                            </div>
                            <div class="item-card-body">
                                <div>
                                    <div class="item-card-title">
                                        <?= htmlspecialchars($pObj->getName()) ?>
                                    </div>
                                    <div class="item-card-desc">
                                        <?php if ($pObj instanceof CannabisFlower): ?>
                                            สายพันธุ์: <?= htmlspecialchars($pObj->getStrainType()) ?> | THC: <?= number_format($pObj->getThcPercent(), 1) ?>%
                                        <?php elseif ($pObj instanceof CannabisOil): ?>
                                            วิธีสกัด: <?= htmlspecialchars($pObj->getExtractionMethod()) ?>
                                        <?php elseif ($pObj instanceof CannabisPlant): ?>
                                            อายุต้นกล้า: <?= $pObj->getAgeWeeks() ?> สัปดาห์
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="item-card-footer">
                                    <div>
                                        <div class="item-card-price">
                                            ฿<?= number_format($pObj->getBasePrice(), 2) ?>
                                        </div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted);">
                                            คงเหลือ: <?= $pObj->getStockDisplay() ?>
                                        </div>
                                    </div>
                                    <span class="btn btn-primary btn-sm">+ ใส่ตะกร้า</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- คอลัมน์ขวา: ตะกร้าสินค้าและการคิดเงิน (สามารถกด ✕ ปิดได้) -->
            <div class="pos-cart-panel" id="posCartPanel">
                <div class="cart-panel-card">
                    <div class="card-header" style="margin-bottom: 12px; padding-bottom: 8px;">
                        <h2 class="card-title" style="font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
                            <span>🛒</span> ตะกร้าสินค้า (<span id="cartCount">0</span>)
                        </h2>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="clearCart()" title="ล้างตะกร้า">ล้าง</button>
                            <button type="button" class="cart-close-trigger" onclick="toggleCartDrawer()" title="ปิดแถบตะกร้า (Hide Cart)">✕</button>
                        </div>
                    </div>

                    <!-- รายการในตะกร้า -->
                    <div style="max-height: 250px; overflow-y: auto; margin-bottom: 14px; border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 4px;">
                        <table class="cart-table" style="width: 100%; border-collapse: collapse; font-size: 0.86rem;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                                    <th style="padding: 8px 6px; text-align: left;">สินค้า</th>
                                    <th style="padding: 8px 4px; width: 80px; text-align: center;">จำนวน</th>
                                    <th style="padding: 8px 6px; width: 75px; text-align: right;">รวม (฿)</th>
                                    <th style="width: 24px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cartTableBody">
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 32px 0;">
                                        🍃 ตะกร้ายังว่างเปล่า<br><span style="font-size: 0.78rem;">คลิกสินค้าด้านซ้ายเพื่อเพิ่ม</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- กล่องสรุป Bulk Pricing Strategy (Sequence 6) -->
                    <div style="background: var(--bg-surface-subtle); border-radius: var(--radius-sm); border: 1px solid var(--border-color); padding: 14px; margin-bottom: 18px; font-size: 0.88rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: var(--text-secondary);">
                            <span>ยอดรวมสินค้า (Subtotal):</span>
                            <span id="subtotalDisplay" style="color: var(--text-primary); font-weight: 600;">฿0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: var(--color-primary); font-weight: 600;">
                            <span>ส่วนลดโปรโมชั่นน้ำหนัก (Bulk):</span>
                            <span id="discountDisplay">-฿0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700; color: var(--accent-gold); border-top: 1px solid var(--border-color); padding-top: 10px; margin-top: 6px;">
                            <span>ยอดสุทธิ (NET):</span>
                            <span id="netDisplay">฿0.00</span>
                        </div>
                    </div>

                    <!-- ฟอร์มชำระเงิน พร้อมระบบข้อมูลลูกค้าและการตรวจสอบอายุ 20+ CustomerGuard -->
                    <form action="pos.php" method="POST" id="checkoutForm" onsubmit="return validateCheckout()">
                        <input type="hidden" name="action" value="checkout">
                        <input type="hidden" name="cart_items" id="cartItemsInput" value="[]">

                        <!-- กล่องระบบข้อมูลลูกค้า (Customer Details) -->
                        <div class="customer-card-section" style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <div style="font-size: 0.88rem; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 6px;">
                                    <span>👤</span> ข้อมูลลูกค้า (Customer Details)
                                </div>
                                <span style="font-size: 0.72rem; padding: 2px 8px; border-radius: 9999px; background: rgba(212, 175, 55, 0.15); color: var(--accent-gold); border: 1px solid rgba(212, 175, 55, 0.3);">
                                    ต้องอายุ 20+ ปี
                                </span>
                            </div>

                            <!-- 1. ชื่อลูกค้า -->
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label for="customer_name" class="form-label" style="font-size: 0.8rem; margin-bottom: 4px;">
                                    ชื่อ-นามสกุลลูกค้า <span class="required">*</span>
                                </label>
                                <input type="text" id="customer_name" name="customer_name" class="form-control" required placeholder="ระบุชื่อลูกค้า เช่น สมชาย ใจดี" value="สมชาย ใจดี" style="font-size: 0.88rem; padding: 7px 10px;">
                            </div>

                            <!-- 2. อายุ และ เพศ -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="customer_age" class="form-label" style="font-size: 0.8rem; margin-bottom: 4px;">
                                        🎂 อายุ (ปี) <span class="required">*</span>
                                    </label>
                                    <input type="number" id="customer_age" name="customer_age" class="form-control" required min="1" max="120" value="25" placeholder="ระบุอายุ" style="font-size: 0.88rem; padding: 7px 10px;" oninput="onCustomerAgeChanged()">
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="customer_gender" class="form-label" style="font-size: 0.8rem; margin-bottom: 4px;">
                                        ⚧ เพศ <span class="required">*</span>
                                    </label>
                                    <select id="customer_gender" name="customer_gender" class="form-control" required style="font-size: 0.88rem; padding: 7px 10px;" onchange="updateCustomerEligibilityUI()">
                                        <option value="ชาย" selected>ชาย (Male)</option>
                                        <option value="หญิง">หญิง (Female)</option>
                                        <option value="อื่นๆ">อื่นๆ / ไม่ระบุ (Other)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- 3. วันเดือนปีเกิด (ซิงค์กับอายุ) -->
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label for="customer_birthdate" class="form-label" style="font-size: 0.8rem; margin-bottom: 4px; display: flex; justify-content: space-between;">
                                    <span>วันเดือนปีเกิด (ค.ศ.)</span>
                                    <span style="font-size: 0.72rem; color: var(--text-muted);">คำนวณอัตโนมัติ</span>
                                </label>
                                <input type="date" id="customer_birthdate" name="customer_birthdate" class="form-control" style="font-size: 0.85rem; padding: 6px 10px;" value="<?= date('Y-m-d', strtotime('-25 years')) ?>" max="<?= date('Y-m-d') ?>" onchange="onCustomerBirthdateChanged()">
                            </div>

                            <!-- 4. ป้ายสถานะการตรวจคุณสมบัติ 20+ ปี (Live Alert Badge) -->
                            <div id="customerEligibilityAlert" style="padding: 8px 12px; border-radius: 6px; font-size: 0.82rem; font-weight: 500; display: flex; align-items: center; gap: 8px; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.35); color: #4ade80;">
                                <span id="eligibilityIcon" style="font-size: 1rem;">✓</span>
                                <span id="eligibilityMessage">ลูกค้าอายุ 25 ปี: ผ่านเกณฑ์ตามกฎหมาย (สามารถซื้อได้)</span>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 18px;">
                            <label for="cash_received" class="form-label" style="font-size: 0.85rem; display: flex; justify-content: space-between; align-items: center;">
                                <span>💵 รับเงินสด (Cash Received ฿) <span class="required">*</span></span>
                                <span id="changePreview" style="color: var(--accent-gold); font-weight: 600;">เงินทอน: ฿0.00</span>
                            </label>
                            <input type="number" step="0.01" min="0" id="cash_received" name="cash_received" class="form-control" required placeholder="0.00" oninput="calculateChange()">
                            <div style="display: flex; gap: 6px; margin-top: 8px;">
                                <button type="button" class="btn btn-secondary btn-sm" style="flex: 1; padding: 4px 8px; font-size: 0.78rem;" onclick="setExactCash()">พอดี</button>
                                <button type="button" class="btn btn-secondary btn-sm" style="flex: 1; padding: 4px 8px; font-size: 0.78rem;" onclick="addCash(500)">500</button>
                                <button type="button" class="btn btn-secondary btn-sm" style="flex: 1; padding: 4px 8px; font-size: 0.78rem;" onclick="addCash(1000)">1,000</button>
                                <button type="button" class="btn btn-secondary btn-sm" style="flex: 1; padding: 4px 8px; font-size: 0.78rem;" onclick="addCash(2000)">2,000</button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            ชำระเงินและออกใบเสร็จ CHILL42x
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- ปุ่มเปิดตะกร้าลอยสำหรับ Mobile -->
    <button type="button" class="floating-mobile-cart-btn" onclick="toggleCartDrawer()">
        🛒 ตะกร้าสินค้า (<span id="cartCountMobile">0</span>)
    </button>

    <script>
        let cart = [];

        function toggleCartDrawer() {
            if (window.innerWidth <= 991) {
                document.body.classList.toggle('cart-open');
            } else {
                document.body.classList.toggle('cart-closed');
            }
        }

        function addToCart(id, name, price, type, unit, maxStock) {
            const defaultStep = (type === 'flower') ? 1.0 : 1;
            const existing = cart.find(item => item.id === id);
            if (existing) {
                if (existing.qty + defaultStep <= maxStock) {
                    existing.qty = Math.round((existing.qty + defaultStep) * 10) / 10;
                } else {
                    alert('สต็อกคงเหลือไม่เพียงพอ (มีจำหน่าย ' + maxStock + ' ' + unit + ')');
                }
            } else {
                cart.push({
                    id: id,
                    name: name,
                    price: price,
                    type: type,
                    unit: unit,
                    maxStock: maxStock,
                    qty: (type === 'flower' ? 3.5 : 1) // default 3.5g for flower jar
                });
            }
            renderCart();
            // ถ้าแถบตะกร้าถูกปิดอยู่บน Desktop ให้เปิดอัตโนมัติเมื่อกดเพิ่มสินค้า
            document.body.classList.remove('cart-closed');
        }

        function updateQty(id, newQty) {
            const item = cart.find(i => i.id === id);
            if (item) {
                const val = parseFloat(newQty);
                if (isNaN(val) || val <= 0) {
                    removeFromCart(id);
                } else if (val > item.maxStock) {
                    alert('สต็อกคงเหลือไม่เพียงพอ (คงเหลือ ' + item.maxStock + ' ' + item.unit + ')');
                    item.qty = item.maxStock;
                    renderCart();
                } else {
                    item.qty = val;
                    renderCart();
                }
            }
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            renderCart();
        }

        function clearCart() {
            cart = [];
            renderCart();
        }

        function renderCart() {
            const tbody = document.getElementById('cartTableBody');
            const cartCount = document.getElementById('cartCount');
            const cartCountHeader = document.getElementById('cartCountHeader');
            const cartCountMobile = document.getElementById('cartCountMobile');
            const cartInput = document.getElementById('cartItemsInput');
            
            cartCount.innerText = cart.length;
            if (cartCountHeader) cartCountHeader.innerText = cart.length;
            if (cartCountMobile) cartCountMobile.innerText = cart.length;
            cartInput.value = JSON.stringify(cart);

            if (cart.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 32px 0;">🍃 ตะกร้ายังว่างเปล่า<br><span style="font-size: 0.78rem;">คลิกสินค้าด้านซ้ายเพื่อเพิ่ม</span></td></tr>';
                document.getElementById('subtotalDisplay').innerText = '฿0.00';
                document.getElementById('discountDisplay').innerText = '-฿0.00';
                document.getElementById('netDisplay').innerText = '฿0.00';
                document.getElementById('cash_received').value = '';
                return;
            }

            let subtotal = 0;
            let totalDiscount = 0;
            tbody.innerHTML = '';

            cart.forEach(item => {
                const itemSubtotal = item.qty * item.price;
                // คำนวณส่วนลด BulkPricing จำลองในฝั่ง client
                let discountRate = 0;
                if (item.type === 'flower') {
                    if (item.qty >= 28) discountRate = 0.20;
                    else if (item.qty >= 14) discountRate = 0.15;
                    else if (item.qty >= 7) discountRate = 0.10;
                    else if (item.qty >= 3.5) discountRate = 0.05;
                }

                const itemDiscount = itemSubtotal * discountRate;
                const finalItemPrice = itemSubtotal - itemDiscount;

                subtotal += itemSubtotal;
                totalDiscount += itemDiscount;

                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid var(--border-subtle)';
                tr.innerHTML = `
                    <td style="padding: 8px 6px;">
                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.88rem;">${item.name}</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">฿${item.price.toFixed(2)}/${item.unit}</div>
                    </td>
                    <td style="text-align: center; padding: 8px 4px;">
                        <input type="number" step="${item.type === 'flower' ? '0.5' : '1'}" min="0.1" 
                               value="${item.qty}" style="width: 58px; padding: 4px; text-align: center; background: var(--bg-surface-subtle); border: 1px solid var(--border-color); color: #fff; border-radius: 4px;" 
                               onchange="updateQty(${item.id}, this.value)">
                    </td>
                    <td style="text-align: right; font-weight: 600; color: var(--accent-gold); padding: 8px 6px;">
                        ฿${finalItemPrice.toFixed(2)}
                    </td>
                    <td style="text-align: right; padding: 8px 2px;">
                        <button type="button" onclick="removeFromCart(${item.id})" style="border:none; background:none; color:#f87171; cursor:pointer; font-weight:bold; font-size:1.1rem;">&times;</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            const netTotal = Math.max(0, subtotal - totalDiscount);
            document.getElementById('subtotalDisplay').innerText = '฿' + subtotal.toFixed(2);
            document.getElementById('discountDisplay').innerText = '-฿' + totalDiscount.toFixed(2);
            document.getElementById('netDisplay').innerText = '฿' + netTotal.toFixed(2);
            document.getElementById('cash_received').value = netTotal > 0 ? netTotal.toFixed(2) : '';
            calculateChange();
        }

        function calculateChange() {
            const netStr = document.getElementById('netDisplay').innerText.replace('฿', '').replace(',', '');
            const net = parseFloat(netStr || 0);
            const cash = parseFloat(document.getElementById('cash_received').value || 0);
            const change = Math.max(0, cash - net);
            const changeEl = document.getElementById('changePreview');
            if (!changeEl) return;

            if (net > 0 && cash >= net) {
                changeEl.innerText = 'เงินทอน: ฿' + change.toFixed(2);
                changeEl.style.color = 'var(--accent-gold)';
            } else if (net > 0 && cash < net) {
                changeEl.innerText = 'ขาดอีก: ฿' + (net - cash).toFixed(2);
                changeEl.style.color = 'var(--status-danger)';
            } else {
                changeEl.innerText = 'เงินทอน: ฿0.00';
                changeEl.style.color = 'var(--accent-gold)';
            }
        }

        function setExactCash() {
            const netStr = document.getElementById('netDisplay').innerText.replace('฿', '').replace(',', '');
            document.getElementById('cash_received').value = parseFloat(netStr || 0).toFixed(2);
            calculateChange();
        }

        function addCash(amount) {
            document.getElementById('cash_received').value = amount.toFixed(2);
            calculateChange();
        }

        function filterCatalog(type, btn) {
            document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const cards = document.querySelectorAll('.catalog-card');
            cards.forEach(card => {
                if (type === 'all' || card.getAttribute('data-type') === type) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // ฟังก์ชันอัปเดตสถานะการตรวจสิทธิ์ลูกค้า (Customer Age & Eligibility)
        function updateCustomerEligibilityUI() {
            const nameInput = document.getElementById('customer_name');
            const ageInput = document.getElementById('customer_age');
            const genderSelect = document.getElementById('customer_gender');
            const alertBox = document.getElementById('customerEligibilityAlert');
            const iconSpan = document.getElementById('eligibilityIcon');
            const msgSpan = document.getElementById('eligibilityMessage');

            const name = (nameInput?.value || '').trim() || 'ลูกค้า';
            const age = parseInt(ageInput?.value || 0, 10);
            const gender = genderSelect?.value || 'ไม่ระบุ';

            if (isNaN(age) || age <= 0) {
                alertBox.style.background = 'rgba(234, 179, 8, 0.15)';
                alertBox.style.borderColor = 'rgba(234, 179, 8, 0.35)';
                alertBox.style.color = '#facc15';
                iconSpan.innerText = '⚠️';
                msgSpan.innerText = 'กรุณาระบุอายุของลูกค้าเพื่อตรวจสอบคุณสมบัติ';
                return false;
            }

            if (age >= 20) {
                // ผ่านเกณฑ์ 20+ ปีบริบูรณ์
                alertBox.style.background = 'rgba(34, 197, 94, 0.15)';
                alertBox.style.borderColor = 'rgba(34, 197, 94, 0.35)';
                alertBox.style.color = '#4ade80';
                iconSpan.innerText = '✓';
                msgSpan.innerText = `ลูกค้าชื่อ '${name}' อายุ ${age} ปี (เพศ: ${gender}): ผ่านเกณฑ์ สามารถซื้อได้`;
                return true;
            } else {
                // อายุไม่ถึง 20 ปี -> แจ้งว่าไม่สามารถซื้อได้
                alertBox.style.background = 'rgba(239, 68, 68, 0.18)';
                alertBox.style.borderColor = 'rgba(239, 68, 68, 0.45)';
                alertBox.style.color = '#f87171';
                iconSpan.innerText = '❌';
                msgSpan.innerText = `ไม่สามารถซื้อได้! ลูกค้าชื่อ '${name}' อายุ ${age} ปี (ต้องอายุ 20 ปีขึ้นไปตามกฎหมาย)`;
                return false;
            }
        }

        // เมื่อกรอกอายุโดยตรง ซิงค์วันเกิดคร่าวๆ
        function onCustomerAgeChanged() {
            const ageInput = document.getElementById('customer_age');
            const birthInput = document.getElementById('customer_birthdate');
            const age = parseInt(ageInput.value || 0, 10);

            if (!isNaN(age) && age > 0) {
                const today = new Date();
                const birthYear = today.getFullYear() - age;
                const m = String(today.getMonth() + 1).padStart(2, '0');
                const d = String(today.getDate()).padStart(2, '0');
                birthInput.value = `${birthYear}-${m}-${d}`;
            }
            updateCustomerEligibilityUI();
        }

        // เมื่อเปลี่ยนวันเกิด ซิงค์อายุที่แท้จริง
        function onCustomerBirthdateChanged() {
            const birthInput = document.getElementById('customer_birthdate');
            const ageInput = document.getElementById('customer_age');

            if (birthInput.value) {
                const dob = new Date(birthInput.value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const m = today.getMonth() - dob.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                ageInput.value = Math.max(0, age);
            }
            updateCustomerEligibilityUI();
        }

        // ตรวจสอบความถูกต้องก่อนกดชำระเงิน
        function validateCheckout() {
            if (cart.length === 0) {
                alert('กรุณาเลือกสินค้าใส่ตะกร้าก่อนดำเนินการชำระเงิน');
                return false;
            }

            const customerName = (document.getElementById('customer_name')?.value || '').trim();
            if (!customerName) {
                alert('กรุณาระบุชื่อลูกค้า');
                document.getElementById('customer_name')?.focus();
                return false;
            }

            const customerGender = (document.getElementById('customer_gender')?.value || '').trim();
            if (!customerGender) {
                alert('กรุณาระบุเพศของลูกค้า');
                document.getElementById('customer_gender')?.focus();
                return false;
            }

            const age = parseInt(document.getElementById('customer_age')?.value || 0, 10);
            if (isNaN(age) || age <= 0) {
                alert('กรุณาระบุอายุของลูกค้า');
                document.getElementById('customer_age')?.focus();
                return false;
            }

            // เงื่อนไขสำคัญ: ต้องอายุ 20 ปีขึ้นไป ถ้าไม่ถึงแจ้งว่าไม่สามารถซื้อได้
            if (age < 20) {
                alert('❌ ไม่สามารถซื้อได้!\n\nลูกค้าชื่อ: ' + customerName + '\nอายุ: ' + age + ' ปี (เพศ: ' + customerGender + ')\n\nระบบไม่อนุญาตให้ทำรายการขาย เนื่องจากลูกค้าต้องมีอายุตั้งแต่ 20 ปีขึ้นไปตาม พ.ร.บ. คุ้มครองและส่งเสริมภูมิปัญญาการแพทย์แผนไทย (กัญชาเป็นสมุนไพรควบคุม)');
                return false;
            }

            const cash = parseFloat(document.getElementById('cash_received').value || 0);
            const netStr = document.getElementById('netDisplay').innerText.replace('฿', '').replace(',', '');
            const net = parseFloat(netStr || 0);

            if (cash < net) {
                alert('ยอดเงินสดที่รับมา (฿' + cash.toFixed(2) + ') น้อยกว่ายอดสุทธิ (฿' + net.toFixed(2) + ')');
                document.getElementById('cash_received')?.focus();
                return false;
            }

            // ส่งข้อมูลตะกร้าสินค้าแบบสมบูรณ์
            document.getElementById('cartItemsInput').value = JSON.stringify(cart);
            return true;
        }

        // เริ่มต้นตรวจสอบสถานะสิทธิ์ลูกค้า
        document.addEventListener('DOMContentLoaded', function() {
            updateCustomerEligibilityUI();
            const nameInput = document.getElementById('customer_name');
            if (nameInput) {
                nameInput.addEventListener('input', updateCustomerEligibilityUI);
            }
        });
    </script>
</body>
</html>

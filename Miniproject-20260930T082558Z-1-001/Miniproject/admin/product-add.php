<?php
/**
 * CHILL42x Dispensary - Add Product (Admin Only)
 * รองรับการเพิ่มสินค้า 3 คลาสตามสถาปัตยกรรม OOP
 * ผสานคุณสมบัติจากส่วน Admin ในธีม Mostudio Dark Luxury
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin('../login.php');

require_once __DIR__ . '/../config/Database.php';

$errorMessage = '';
$successMessage = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? 'flower');
    $basePrice = floatval($_POST['base_price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');

    // ฟิลด์เฉพาะตามประเภท
    $strainType = ($type === 'flower') ? trim($_POST['strain_type'] ?? '') : null;
    $thcPercent = ($type === 'flower') ? floatval($_POST['thc_percent'] ?? 20.0) : null;
    $stockGrams = ($type === 'flower') ? floatval($_POST['stock_grams'] ?? 0) : null;

    $extractionMethod = ($type === 'oil') ? trim($_POST['extraction_method'] ?? '') : null;
    $stockBottles = ($type === 'oil') ? intval($_POST['stock_bottles'] ?? 0) : null;

    $ageWeeks = ($type === 'plant') ? intval($_POST['age_weeks'] ?? 0) : null;
    $stockPieces = ($type === 'plant') ? intval($_POST['stock_pieces'] ?? 0) : null;

    $stockQuantity = ($type === 'flower') ? $stockGrams : (($type === 'oil') ? $stockBottles : $stockPieces);

    if (empty($imageUrl)) {
        if ($type === 'flower') $imageUrl = 'assets/images/flower_kush.jpg';
        elseif ($type === 'oil') $imageUrl = 'assets/images/cannabis_oil.jpg';
        else $imageUrl = 'assets/images/cannabis_plant.jpg';
    }

    // ตรวจสอบความถูกต้องของข้อมูล
    if (empty($name) || empty($type) || $basePrice <= 0) {
        $errorMessage = 'กรุณากรอกชื่อสินค้า เลือกประเภทสินค้า และกำหนดราคาเริ่มต้นให้ถูกต้อง (> 0 บาท)';
    } elseif ($type === 'flower' && empty($strainType)) {
        $errorMessage = 'กรุณาระบุสายพันธุ์ (Strain Type) สำหรับช่อดอกกัญชา';
    } elseif ($type === 'oil' && empty($extractionMethod)) {
        $errorMessage = 'กรุณาระบุวิธีการสกัด (Extraction Method) สำหรับน้ำมันสายหยอด';
    } else {
        $db = Database::getInstance()->getConnection();
        if ($db === null) {
            // โหมด In-Memory / Standalone Fallback
            $_SESSION['flash_success'] = "จำลองการเพิ่มสินค้า '{$name}' เรียบร้อยแล้ว (โหมด In-Memory)";
            header("Location: product-list.php");
            exit();
        } else {
            try {
                // พยายามบันทึกแบบครอบคลุมทั้ง schema.sql และ database.sql
                try {
                    $stmt = $db->prepare("INSERT INTO products 
                        (name, type, product_type, base_price, strain_type, thc_percent, stock_grams, extraction_method, stock_bottles, age_weeks, stock_pieces, stock_quantity, image_url, description)
                        VALUES (:name, :type, :ptype, :base_price, :strain_type, :thc_percent, :stock_grams, :extraction_method, :stock_bottles, :age_weeks, :stock_pieces, :stock_quantity, :image_url, :description)");

                    $stmt->execute([
                        ':name' => $name,
                        ':type' => $type,
                        ':ptype' => $type,
                        ':base_price' => $basePrice,
                        ':strain_type' => $strainType,
                        ':thc_percent' => $thcPercent,
                        ':stock_grams' => $stockGrams,
                        ':extraction_method' => $extractionMethod,
                        ':stock_bottles' => $stockBottles,
                        ':age_weeks' => $ageWeeks,
                        ':stock_pieces' => $stockPieces,
                        ':stock_quantity' => $stockQuantity,
                        ':image_url' => $imageUrl,
                        ':description' => $description
                    ]);
                } catch (Exception $e1) {
                    $stmt = $db->prepare("INSERT INTO products 
                        (name, type, base_price, strain_type, stock_grams, extraction_method, stock_bottles, age_weeks, stock_pieces, description)
                        VALUES (:name, :type, :base_price, :strain_type, :stock_grams, :extraction_method, :stock_bottles, :age_weeks, :stock_pieces, :description)");

                    $stmt->execute([
                        ':name' => $name,
                        ':type' => $type,
                        ':base_price' => $basePrice,
                        ':strain_type' => $strainType,
                        ':stock_grams' => $stockGrams,
                        ':extraction_method' => $extractionMethod,
                        ':stock_bottles' => $stockBottles,
                        ':age_weeks' => $ageWeeks,
                        ':stock_pieces' => $stockPieces,
                        ':description' => $description
                    ]);
                }

                $_SESSION['flash_success'] = "เพิ่มสินค้า '{$name}' เข้าสู่ระบบ CHILL42x เรียบร้อยแล้ว";
                header("Location: product-list.php");
                exit();
            } catch (Exception $e) {
                $errorMessage = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
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
    <title>เพิ่มสินค้าใหม่ - CHILL42x Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .disabled-section {
            opacity: 0.38;
            pointer-events: none;
            filter: grayscale(80%);
            transition: all 0.3s ease;
        }
        .field-section {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .field-section.active-section {
            background: rgba(197, 168, 128, 0.04);
            border-color: rgba(197, 168, 128, 0.4);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }
        .field-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-subtle);
        }
        .section-badge {
            font-size: 0.72rem;
            padding: 3px 10px;
            border-radius: var(--radius-full);
            font-weight: 600;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="app-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    เพิ่มสินค้าใหม่เข้าแคตตาล็อก CHILL42x
                </h1>
                <p class="page-subtitle">กำหนดคุณสมบัติสินค้าตามคลาสในระบบ OOP พร้อมสลับเปิด/ปิดฟิลด์อัตโนมัติ</p>
            </div>
            <a href="product-list.php" class="btn btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                กลับหน้ารายการสินค้า
            </a>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span><?= htmlspecialchars($errorMessage) ?></span>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 900px; margin: 0 auto;">
            <form action="product-add.php" method="POST" id="productForm">
                <!-- 1. ข้อมูลพื้นฐานสินค้า (Common Attributes) -->
                <div class="card-header">
                    <h2 class="card-title">
                        <span>📦</span> 1. ข้อมูลพื้นฐานสินค้า (Base Product Attributes)
                    </h2>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="name" class="form-label">ชื่อสินค้า (Product Name) <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required placeholder="เช่น OG Kush (Top Shelf) หรือ Full Spectrum CBD Drops" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="type" class="form-label">ประเภทคลาส (Class Type) <span class="required">*</span></label>
                        <select id="type" name="type" class="form-control" required onchange="handleTypeChange()">
                            <option value="flower" <?= (($_POST['type'] ?? 'flower') === 'flower') ? 'selected' : '' ?>>🌸 ช่อดอก (CannabisFlower)</option>
                            <option value="oil" <?= (($_POST['type'] ?? '') === 'oil') ? 'selected' : '' ?>>💧 น้ำมันสายหยอด (CannabisOil)</option>
                            <option value="plant" <?= (($_POST['type'] ?? '') === 'plant') ? 'selected' : '' ?>>🌱 ต้นกล้าพันธุ์ (CannabisPlant)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="base_price" class="form-label">ราคาต่อหน่วย (บาท) <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" id="base_price" name="base_price" class="form-control" required placeholder="เช่น 450.00" value="<?= htmlspecialchars($_POST['base_price'] ?? '') ?>">
                        <div class="form-hint" id="priceHint">ราคาต่อกรัม (สำหรับช่อดอกกัญชา)</div>
                    </div>

                    <div class="form-group" style="flex: 1.5;">
                        <label for="image_url" class="form-label">รูปภาพสินค้า (Image Path)</label>
                        <select id="image_url" name="image_url" class="form-control">
                            <option value="assets/images/flower_kush.jpg">🌸 รูปช่อดอก (flower_kush.jpg)</option>
                            <option value="assets/images/cannabis_oil.jpg">💧 รูปน้ำมันสกัด (cannabis_oil.jpg)</option>
                            <option value="assets/images/cannabis_plant.jpg">🌱 รูปต้นกล้าพันธุ์ (cannabis_plant.jpg)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">รายละเอียดสินค้า / กลิ่นเทอร์ปีน / หมายเหตุ</label>
                    <input type="text" id="description" name="description" class="form-control" placeholder="เช่น กลิ่นสนและซิตรัส อบแห้งและบ่มอย่างดี คัดเกรดพรีเมียม" value="<?= htmlspecialchars($_POST['description'] ?? '') ?>">
                </div>

                <!-- 2. ข้อมูลเฉพาะคลาส (แสดงทั้ง 3 ส่วน พร้อม Auto Disable ช่องที่ไม่เกี่ยวข้อง) -->
                <div class="card-header" style="margin-top: 16px;">
                    <h2 class="card-title">
                        <span>⚙️</span> 2. คุณสมบัติเฉพาะของแต่ละคลาส (OOP Specific Attributes)
                    </h2>
                </div>

                <!-- ส่วนที่ 1: ช่อดอก (CannabisFlower) -->
                <div class="field-section active-section" id="section-flower">
                    <div class="field-section-title">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span>🌸</span> <strong>CannabisFlower (ช่อดอกกัญชา)</strong>
                        </span>
                        <span class="section-badge badge-flower" id="badge-flower">✓ กำลังใช้งาน (Active)</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="flex: 1.2;">
                            <label for="strain_type" class="form-label">สายพันธุ์ (Strain Type) <span class="required">*</span></label>
                            <select id="strain_type" name="strain_type" class="form-control">
                                <option value="Hybrid">Hybrid (ไฮบริด สมดุลผ่อนคลาย)</option>
                                <option value="Sativa">Sativa (ซาติว่า ตื่นตัว สดชื่น)</option>
                                <option value="Indica">Indica (อินดิก้า หลับสบาย ผ่อนคลายลึก)</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 0.8;">
                            <label for="thc_percent" class="form-label">THC % (เปอร์เซ็นต์)</label>
                            <input type="number" step="0.1" min="0" max="100" id="thc_percent" name="thc_percent" class="form-control" placeholder="เช่น 24.5" value="<?= htmlspecialchars($_POST['thc_percent'] ?? '24.5') ?>">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="stock_grams" class="form-label">สต็อกเริ่มต้น (กรัม - ทศนิยมได้) <span class="required">*</span></label>
                            <input type="number" step="0.01" min="0" id="stock_grams" name="stock_grams" class="form-control" placeholder="เช่น 100.50" value="<?= htmlspecialchars($_POST['stock_grams'] ?? '100.00') ?>">
                        </div>
                    </div>
                </div>

                <!-- ส่วนที่ 2: น้ำมันสกัด (CannabisOil) -->
                <div class="field-section disabled-section" id="section-oil">
                    <div class="field-section-title">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span>💧</span> <strong>CannabisOil (น้ำมันสกัดสายหยอด Drops)</strong>
                        </span>
                        <span class="section-badge badge-oil" id="badge-oil">ปิดใช้งาน (Disabled)</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label for="extraction_method" class="form-label">วิธีการสกัด (Extraction Method) <span class="required">*</span></label>
                            <input type="text" id="extraction_method" name="extraction_method" class="form-control" placeholder="เช่น Supercritical CO2 Extraction หรือ Fractional Distillation" value="<?= htmlspecialchars($_POST['extraction_method'] ?? 'Supercritical CO2 Extraction') ?>" disabled>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="stock_bottles" class="form-label">สต็อกเริ่มต้น (ขวด) <span class="required">*</span></label>
                            <input type="number" step="1" min="0" id="stock_bottles" name="stock_bottles" class="form-control" placeholder="เช่น 20" value="<?= htmlspecialchars($_POST['stock_bottles'] ?? '20') ?>" disabled>
                        </div>
                    </div>
                </div>

                <!-- ส่วนที่ 3: ต้นกล้าพันธุ์ (CannabisPlant) -->
                <div class="field-section disabled-section" id="section-plant">
                    <div class="field-section-title">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span>🌱</span> <strong>CannabisPlant (ต้นกล้าพันธุ์/โคลน)</strong>
                        </span>
                        <span class="section-badge badge-plant" id="badge-plant">ปิดใช้งาน (Disabled)</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <label for="age_weeks" class="form-label">อายุต้นกล้า (สัปดาห์) <span class="required">*</span></label>
                            <input type="number" step="1" min="1" id="age_weeks" name="age_weeks" class="form-control" placeholder="เช่น 4" value="<?= htmlspecialchars($_POST['age_weeks'] ?? '4') ?>" disabled>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="stock_pieces" class="form-label">สต็อกเริ่มต้น (ต้น) <span class="required">*</span></label>
                            <input type="number" step="1" min="0" id="stock_pieces" name="stock_pieces" class="form-control" placeholder="เช่น 15" value="<?= htmlspecialchars($_POST['stock_pieces'] ?? '10') ?>" disabled>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 14px; margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--border-subtle);">
                    <a href="product-list.php" class="btn btn-secondary">ยกเลิก</a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        บันทึกสินค้าใหม่เข้าคลัง
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function handleTypeChange() {
            const type = document.getElementById('type').value;
            const priceHint = document.getElementById('priceHint');
            const imageSelect = document.getElementById('image_url');

            // Elements for Flower
            const secFlower = document.getElementById('section-flower');
            const badgeFlower = document.getElementById('badge-flower');
            const strainType = document.getElementById('strain_type');
            const thcPercent = document.getElementById('thc_percent');
            const stockGrams = document.getElementById('stock_grams');

            // Elements for Oil
            const secOil = document.getElementById('section-oil');
            const badgeOil = document.getElementById('badge-oil');
            const extractMethod = document.getElementById('extraction_method');
            const stockBottles = document.getElementById('stock_bottles');

            // Elements for Plant
            const secPlant = document.getElementById('section-plant');
            const badgePlant = document.getElementById('badge-plant');
            const ageWeeks = document.getElementById('age_weeks');
            const stockPieces = document.getElementById('stock_pieces');

            // 1. Reset all to disabled
            [strainType, thcPercent, stockGrams, extractMethod, stockBottles, ageWeeks, stockPieces].forEach(el => {
                if (el) el.disabled = true;
            });
            [secFlower, secOil, secPlant].forEach(sec => {
                sec.classList.add('disabled-section');
                sec.classList.remove('active-section');
            });
            badgeFlower.innerText = 'ปิดใช้งาน (Disabled)';
            badgeOil.innerText = 'ปิดใช้งาน (Disabled)';
            badgePlant.innerText = 'ปิดใช้งาน (Disabled)';

            // 2. Enable specific section based on selected type
            if (type === 'flower') {
                secFlower.classList.remove('disabled-section');
                secFlower.classList.add('active-section');
                strainType.disabled = false;
                thcPercent.disabled = false;
                stockGrams.disabled = false;
                badgeFlower.innerText = '✓ กำลังใช้งาน (Active)';
                priceHint.innerText = 'ราคาต่อ 1 กรัม (THB/gram) - ช่อดอกกัญชา';
                if (imageSelect) imageSelect.value = 'assets/images/flower_kush.jpg';
            } else if (type === 'oil') {
                secOil.classList.remove('disabled-section');
                secOil.classList.add('active-section');
                extractMethod.disabled = false;
                stockBottles.disabled = false;
                badgeOil.innerText = '✓ กำลังใช้งาน (Active)';
                priceHint.innerText = 'ราคาต่อ 1 ขวด (THB/bottle) - น้ำมันสายหยอด';
                if (imageSelect) imageSelect.value = 'assets/images/cannabis_oil.jpg';
            } else if (type === 'plant') {
                secPlant.classList.remove('disabled-section');
                secPlant.classList.add('active-section');
                ageWeeks.disabled = false;
                stockPieces.disabled = false;
                badgePlant.innerText = '✓ กำลังใช้งาน (Active)';
                priceHint.innerText = 'ราคาต่อ 1 ต้น (THB/piece) - ต้นกล้าพันธุ์แท้';
                if (imageSelect) imageSelect.value = 'assets/images/cannabis_plant.jpg';
            }
        }

        document.addEventListener('DOMContentLoaded', handleTypeChange);
    </script>
</body>
</html>

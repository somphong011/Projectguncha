<?php
/**
 * CHILL42x Dispensary - หน้าร้านค้าหลัก (Storefront Homepage)
 * Mostudio Dark Luxury Aesthetic & Showcase ผลิตภัณฑ์แนะนำยอดนิยม
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/ProductFactory.php';
require_once __DIR__ . '/classes/CustomerGuard.php';
require_once __DIR__ . '/classes/Customer.php';

$db = Database::getInstance()->getConnection();

$products = [];
if ($db !== null) {
    try {
        $stmt = $db->query("SELECT p.*, IFNULL(p.type, p.product_type) as resolved_type,
            IFNULL(p.stock_grams, IFNULL(p.stock_bottles, IFNULL(p.stock_pieces, p.stock_quantity))) as current_stock_calc
            FROM products p 
            ORDER BY p.id ASC");
        if ($stmt) {
            $products = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        $products = [];
    }
}

// Fallback ข้อมูลสินค้าพร้อมรูปภาพเฉพาะตัว
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
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHILL42x - Craft Cannabis Dispensary & Botanical Oil Drops</title>
    <meta name="description" content="ร้านจำหน่ายกัญชาคราฟต์เกรดพรีเมียม ช่อดอกคัดพิเศษ น้ำมันสกัดสายหยอดบริสุทธิ์ และต้นแม่พันธุ์แท้มาตรฐานสากล">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Hero Section Styling */
        .storefront-hero {
            position: relative;
            min-height: 520px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 80px 24px 60px;
            border-radius: var(--radius-lg);
            margin-bottom: 48px;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(10, 11, 13, 0.45) 0%, rgba(10, 11, 13, 0.95) 100%),
                        url('assets/images/dispensary_hero.jpg') center/cover no-repeat;
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }

        .hero-inner {
            max-width: 820px;
            z-index: 2;
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 9999px;
            background: rgba(212, 175, 55, 0.12);
            border: 1px solid rgba(212, 175, 55, 0.35);
            color: var(--accent-gold);
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .hero-title {
            font-size: 2.8rem;
            font-weight: 700;
            line-height: 1.25;
            color: #ffffff;
            margin-bottom: 16px;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.7);
        }

        .hero-title span {
            background: linear-gradient(135deg, #f5d77f 0%, #d4af37 60%, #aa820a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.15rem;
            line-height: 1.65;
            color: #d1d5db;
            margin-bottom: 32px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.8);
        }

        .hero-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 36px;
        }

        .hero-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            padding: 14px 28px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 1.05rem;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35);
            transition: all 0.25s ease;
        }

        .hero-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(16, 185, 129, 0.5);
            color: #ffffff;
        }

        .hero-btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(10px);
            color: #ffffff;
            padding: 14px 26px;
            border-radius: var(--radius-sm);
            font-weight: 500;
            font-size: 1.05rem;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.25s ease;
        }

        .hero-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--accent-gold);
            color: var(--accent-gold);
            transform: translateY(-2px);
        }

        .hero-features {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .hero-feat-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #e5e7eb;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .hero-feat-item span.icon {
            color: var(--accent-gold);
        }

        /* Section Headers */
        .section-header-box {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-top: 4px;
        }

        /* Category Filter Pills */
        .catalog-filter-bar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 14px 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .filter-pills-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .catalog-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 8px 18px;
            border-radius: 9999px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .catalog-pill:hover,
        .catalog-pill.active {
            background: var(--accent-gold);
            color: #000000;
            border-color: var(--accent-gold);
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(212, 175, 55, 0.3);
        }

        /* Product Cards Grid */
        .showcase-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }

        .showcase-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        .showcase-card:hover {
            transform: translateY(-6px);
            border-color: rgba(212, 175, 55, 0.45);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.6);
        }

        .showcase-thumb-box {
            position: relative;
            width: 100%;
            height: 220px;
            background: #000000;
            overflow: hidden;
        }

        .showcase-thumb-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .showcase-card:hover .showcase-thumb-box img {
            transform: scale(1.05);
        }

        .showcase-tag {
            position: absolute;
            top: 14px;
            left: 14px;
            z-index: 2;
        }

        .showcase-body {
            padding: 22px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .showcase-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
            line-height: 1.35;
        }

        .showcase-spec-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            color: var(--accent-gold);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .showcase-desc {
            color: var(--text-secondary);
            font-size: 0.92rem;
            line-height: 1.55;
            margin-bottom: 20px;
            flex: 1;
        }

        .showcase-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
            margin-top: auto;
        }

        .showcase-price-box {
            display: flex;
            flex-direction: column;
        }

        .showcase-price-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .showcase-price-value {
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--accent-gold);
            font-family: var(--font-heading);
        }

        .showcase-order-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-size: 0.92rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
            transition: all 0.2s ease;
        }

        .showcase-order-btn:hover {
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.5);
            transform: translateY(-2px);
            color: #ffffff;
        }

        /* Bulk Pricing Banner */
        .promo-banner {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.1) 0%, rgba(16, 185, 129, 0.08) 100%),
                        var(--bg-card);
            border: 1px solid rgba(212, 175, 55, 0.35);
            border-radius: var(--radius-lg);
            padding: 36px 32px;
            margin-bottom: 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
        }

        .promo-tiers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .promo-tier-item {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            text-align: center;
        }

        .promo-tier-qty {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .promo-tier-discount {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-gold);
        }

        /* Store Experience Grid */
        .experience-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 48px;
        }

        .exp-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 24px;
            transition: all 0.25s ease;
        }

        .exp-card:hover {
            border-color: rgba(212, 175, 55, 0.4);
            transform: translateY(-3px);
        }

        .exp-icon {
            font-size: 2.2rem;
            margin-bottom: 14px;
        }

        .exp-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .exp-desc {
            font-size: 0.88rem;
            color: var(--text-secondary);
            line-height: 1.55;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.1rem;
            }
            .storefront-hero {
                padding: 60px 16px 40px;
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="page-container" style="max-width: 1240px; margin: 0 auto; padding: 24px 20px 80px;">
        
        <!-- 1. Hero Showcase Banner -->
        <section class="storefront-hero">
            <div class="hero-inner">
                <div class="hero-pill">
                    <span>✨</span> CHILL42x CRAFT DISPENSARY & OIL DROPS
                </div>
                <h1 class="hero-title">
                    สัมผัสศาสตร์แห่ง<span>กัญชาคราฟต์</span><br>ระดับพรีเมียมมาตรฐานสากล
                </h1>
                <p class="hero-subtitle">
                    คัดสรรช่อดอกเกรด Top Shelf สายพันธุ์แท้ น้ำมันสกัดเย็นสายหยอดบริสุทธิ์สูง และต้นแม่พันธุ์พร้อมปลูก เพาะปลูกในระบบควบคุมสภาพแวดล้อมมาตรฐานห้องปฏิบัติการ
                </p>
                <div class="hero-actions">
                    <a href="pos.php" class="hero-btn-primary">
                        <span>🛒</span> สั่งซื้อสินค้าที่ POS หน้าร้าน
                    </a>
                    <a href="#catalog" class="hero-btn-secondary">
                        <span>🌿</span> สำรวจผลิตภัณฑ์แนะนำ
                    </a>
                </div>
                <div class="hero-features">
                    <div class="hero-feat-item">
                        <span class="icon">🛡️</span> ตรวจสอบอายุ 20+ ตามกฎหมาย
                    </div>
                    <div class="hero-feat-item">
                        <span class="icon">🔬</span> วิเคราะห์มาตรฐาน Lab Tested
                    </div>
                    <div class="hero-feat-item">
                        <span class="icon">⚖️</span> ส่วนลดราคาส่ง Bulk Pricing ทันที
                    </div>
                    <div class="hero-feat-item">
                        <span class="icon">🌱</span> ปลูกแบบออร์แกนิก 100%
                    </div>
                </div>
            </div>
        </section>

        <!-- ระบบของลูกค้า: ตรวจสอบสิทธิ์ก่อนสั่งซื้อ (บอกชื่อ, บอกอายุ, บอกเพศ, ต้องอายุ 20 ปีขึ้นไป) -->
        <section class="customer-checker-section" style="background: linear-gradient(135deg, rgba(20, 24, 33, 0.95) 0%, rgba(15, 17, 23, 0.95) 100%); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: var(--radius-lg); padding: 32px 28px; margin-bottom: 48px; box-shadow: 0 16px 32px rgba(0, 0, 0, 0.5);">
            <div style="max-width: 860px; margin: 0 auto;">
                <div style="text-align: center; margin-bottom: 24px;">
                    <div class="hero-pill" style="margin-bottom: 12px; background: rgba(212, 175, 55, 0.12); color: var(--accent-gold);">
                        <span>🛡️</span> CUSTOMER VERIFICATION SYSTEM
                    </div>
                    <h2 style="font-size: 1.7rem; color: #ffffff; margin-bottom: 8px;">
                        ระบบตรวจสอบสิทธิ์ลูกค้า (Customer Verification)
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0 auto; max-width: 650px;">
                        ตาม พ.ร.บ. คุ้มครองและส่งเสริมภูมิปัญญาการแพทย์แผนไทย ผู้ซื้อต้องมีอายุ <strong>20 ปีขึ้นไป</strong> กรุณาระบุชื่อ อายุ และเพศ เพื่อตรวจสอบคุณสมบัติก่อนทำการสั่งซื้อ
                    </p>
                </div>

                <div style="background: rgba(0, 0, 0, 0.35); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 22px;">
                    <form id="storefrontCustomerForm" onsubmit="event.preventDefault(); checkStorefrontEligibility();">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px;">
                            <!-- 1. บอกชื่อ -->
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="sf_customer_name" class="form-label" style="font-size: 0.85rem; color: #e5e7eb; margin-bottom: 6px;">
                                    👤 ชื่อ-นามสกุล <span class="required">*</span>
                                </label>
                                <input type="text" id="sf_customer_name" class="form-control" required placeholder="เช่น สมชาย ใจดี" style="font-size: 0.95rem;">
                            </div>

                            <!-- 2. บอกอายุ -->
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="sf_customer_age" class="form-label" style="font-size: 0.85rem; color: #e5e7eb; margin-bottom: 6px;">
                                    🎂 อายุ (ปี) <span class="required">*</span>
                                </label>
                                <input type="number" id="sf_customer_age" class="form-control" required min="1" max="120" placeholder="เช่น 25" style="font-size: 0.95rem;">
                            </div>

                            <!-- 3. บอกเพศ -->
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="sf_customer_gender" class="form-label" style="font-size: 0.85rem; color: #e5e7eb; margin-bottom: 6px;">
                                    ⚧ เพศ <span class="required">*</span>
                                </label>
                                <select id="sf_customer_gender" class="form-control" required style="font-size: 0.95rem;">
                                    <option value="">-- เลือกเพศ --</option>
                                    <option value="ชาย">ชาย (Male)</option>
                                    <option value="หญิง">หญิง (Female)</option>
                                    <option value="อื่นๆ">อื่นๆ / ไม่ระบุ (Other)</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                            <button type="submit" class="hero-btn-primary" style="padding: 12px 32px; border: none; cursor: pointer; font-size: 1rem;">
                                <span>🔍</span> ตรวจสอบสิทธิ์การซื้อ
                            </button>
                        </div>
                    </form>

                    <!-- ผลการตรวจสอบ -->
                    <div id="sfResultBox" style="display: none; margin-top: 20px; padding: 18px 22px; border-radius: var(--radius-sm); transition: all 0.3s ease;">
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Catalog & Filter Section -->
        <section id="catalog" style="scroll-margin-top: 100px;">
            <div class="section-header-box">
                <div>
                    <h2 class="section-title">
                        <span>🌿</span> ผลิตภัณฑ์แนะนำยอดนิยม (Featured Products)
                    </h2>
                    <p class="section-subtitle">
                        เลือกชมผลิตภัณฑ์เกรดพรีเมียม ทุกรายการพร้อมสั่งซื้อและออกสลิปใบเสร็จได้ทันทีที่จุดบริการ POS
                    </p>
                </div>
                <div>
                    <a href="pos.php" class="btn btn-secondary btn-sm">
                        เปิดระบบแคชเชียร์ POS ➔
                    </a>
                </div>
            </div>

            <!-- Filter Pills -->
            <div class="catalog-filter-bar">
                <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                    ตัวกรองหมวดหมู่:
                </div>
                <div class="filter-pills-group">
                    <button type="button" class="catalog-pill active" onclick="filterCatalog('all', this)">
                        ทั้งหมด (All)
                    </button>
                    <button type="button" class="catalog-pill" onclick="filterCatalog('flower', this)">
                        🌸 ช่อดอกคราฟต์
                    </button>
                    <button type="button" class="catalog-pill" onclick="filterCatalog('oil', this)">
                        💧 สกัดเย็นสายหยอด
                    </button>
                    <button type="button" class="catalog-pill" onclick="filterCatalog('plant', this)">
                        🌱 ต้นกล้าพันธุ์แท้
                    </button>
                </div>
            </div>

            <!-- Products Showcase Grid -->
            <div class="showcase-grid" id="showcaseContainer">
                <?php foreach ($products as $p): 
                    $pObj = ProductFactory::createFromRow($p);
                    if (!$pObj) continue;

                    $type = $pObj->getType();
                    $thumb = 'assets/images/flower_kush.jpg';
                    if (!empty($p['image_url']) && file_exists(__DIR__ . '/' . $p['image_url'])) {
                        $thumb = $p['image_url'];
                    } elseif ($type === 'oil') {
                        $thumb = 'assets/images/cannabis_oil.jpg';
                    } elseif ($type === 'plant') {
                        $thumb = 'assets/images/cannabis_plant.jpg';
                    }

                    $badgeText = '🌸 ช่อดอกคราฟต์';
                    $badgeClass = 'badge-flower';
                    $unitPriceLabel = 'ราคาเริ่มต้น / กรัม';
                    if ($type === 'oil') {
                        $badgeText = '💧 สกัดเย็นสายหยอด';
                        $badgeClass = 'badge-oil';
                        $unitPriceLabel = 'ราคา / ขวด (30ml)';
                    } elseif ($type === 'plant') {
                        $badgeText = '🌱 ต้นกล้าพันธุ์แท้';
                        $badgeClass = 'badge-plant';
                        $unitPriceLabel = 'ราคา / ต้น';
                    }
                ?>
                    <article class="showcase-card" data-category="<?= $type ?>">
                        <div class="showcase-thumb-box">
                            <img src="<?= $thumb ?>" alt="<?= htmlspecialchars($pObj->getName()) ?>" loading="lazy">
                            <div class="showcase-tag">
                                <span class="badge <?= $badgeClass ?>">
                                    <?= $badgeText ?>
                                </span>
                            </div>
                        </div>
                        <div class="showcase-body">
                            <h3 class="showcase-title">
                                <?= htmlspecialchars($pObj->getName()) ?>
                            </h3>
                            <div class="showcase-spec-row">
                                <?php if ($pObj instanceof CannabisFlower): ?>
                                    <span>🧬 สายพันธุ์: <?= htmlspecialchars($pObj->getStrainType()) ?></span>
                                    <span>•</span>
                                    <span>⚡ THC: <?= number_format($pObj->getThcPercent(), 1) ?>%</span>
                                <?php elseif ($pObj instanceof CannabisOil): ?>
                                    <span>💧 วิธีสกัด: <?= htmlspecialchars($pObj->getExtractionMethod()) ?></span>
                                <?php elseif ($pObj instanceof CannabisPlant): ?>
                                    <span>🌱 อายุต้นกล้า: <?= $pObj->getAgeWeeks() ?> สัปดาห์ (พร้อมปลูก)</span>
                                <?php endif; ?>
                            </div>
                            <p class="showcase-desc">
                                <?= htmlspecialchars($p['description'] ?? 'ผลิตภัณฑ์คราฟต์คุณภาพสูง คัดสรรอย่างพิถีพิถันเพื่อลูกค้า CHILL42x') ?>
                            </p>
                            <div class="showcase-footer">
                                <div class="showcase-price-box">
                                    <span class="showcase-price-label"><?= $unitPriceLabel ?></span>
                                    <span class="showcase-price-value">฿<?= number_format($pObj->getBasePrice(), 2) ?></span>
                                </div>
                                <a href="pos.php" class="showcase-order-btn">
                                    🛒 สั่งซื้อที่ POS ➔
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- 3. Bulk Pricing Promo Banner -->
        <section class="promo-banner">
            <div style="flex: 1; min-width: 280px;">
                <div class="hero-pill" style="margin-bottom: 10px; background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.35); color: #34d399;">
                    <span>⚖️</span> BULK PRICING TIER ส่วนลดราคาส่งอัตโนมัติ
                </div>
                <h3 style="font-size: 1.6rem; color: #ffffff; margin-bottom: 8px;">
                    ยิ่งซื้อมาก ยิ่งคุ้มค่า ชั่งน้ำหนักคำนวณส่วนลดทันที
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6;">
                    ระบบคำนวณส่วนลดตามน้ำหนักช่อดอกโดยอัตโนมัติที่หน้าจอ POS ไม่ต้องกรอกโค้ด เหมาะสำหรับทั้งลูกค้ารายย่อยและสายช่อดอกคราฟต์
                </p>
                <div class="promo-tiers-grid">
                    <div class="promo-tier-item">
                        <div class="promo-tier-qty">3.5 กรัม (1/8 oz)</div>
                        <div class="promo-tier-discount">ลด 5%</div>
                    </div>
                    <div class="promo-tier-item">
                        <div class="promo-tier-qty">7 กรัม (1/4 oz)</div>
                        <div class="promo-tier-discount">ลด 10%</div>
                    </div>
                    <div class="promo-tier-item">
                        <div class="promo-tier-qty">14 กรัม (1/2 oz)</div>
                        <div class="promo-tier-discount">ลด 15%</div>
                    </div>
                    <div class="promo-tier-item">
                        <div class="promo-tier-qty">28 กรัม (1 oz)</div>
                        <div class="promo-tier-discount">ลด 20%</div>
                    </div>
                </div>
            </div>
            <div>
                <a href="pos.php" class="hero-btn-primary" style="white-space: nowrap;">
                    <span>🧾</span> ไปที่จุดขาย POS เพื่อคำนวณ
                </a>
            </div>
        </section>

        <!-- 4. Why CHILL42x Experience Section -->
        <section style="margin-bottom: 48px;">
            <div class="section-header-box">
                <div>
                    <h2 class="section-title">
                        <span>⭐</span> มาตรฐานความปลอดภัยและคุณภาพ CHILL42x
                    </h2>
                    <p class="section-subtitle">
                        มั่นใจในคุณภาพและสุขอนามัย ด้วยกระบวนการเพาะปลูกและสกัดที่ตรวจสอบได้ทุกขั้นตอน
                    </p>
                </div>
            </div>

            <div class="experience-grid">
                <div class="exp-card">
                    <div class="exp-icon">🌿</div>
                    <h4 class="exp-title">ช่อดอกเกรด Indoor คราฟต์</h4>
                    <p class="exp-desc">
                        เพาะปลูกในห้องควบคุมอุณหภูมิ ความชื้น และค่าแสงอย่างแม่นยำ ปราศจากสารเคมี ยาฆ่าแมลง และโลหะหนักตกค้าง
                    </p>
                </div>
                <div class="exp-card">
                    <div class="exp-icon">🔬</div>
                    <h4 class="exp-title">สกัดเย็นมาตรฐาน CO2</h4>
                    <p class="exp-desc">
                        สายหยอดสกัดด้วยก๊าซคาร์บอนไดออกไซด์แรงดันวิกฤต (Supercritical CO2) คงคุณค่าเทอร์ปีนธรรมชาติและสารแคนนาบินอยด์ครบถ้วน
                    </p>
                </div>
                <div class="exp-card">
                    <div class="exp-icon">🛡️</div>
                    <h4 class="exp-title">ระบบตรวจสอบอายุ 20+</h4>
                    <p class="exp-desc">
                        ปฏิบัติตาม พ.ร.บ. คุ้มครองและส่งเสริมภูมิปัญญาฯ อย่างเคร่งครัด ด้วยระบบ Guard อัตโนมัติป้องกันการจำหน่ายแก่ผู้มีอายุต่ำกว่าเกณฑ์
                    </p>
                </div>
                <div class="exp-card">
                    <div class="exp-icon">🧾</div>
                    <h4 class="exp-title">ออกสลิปใบเสร็จมาตรฐาน</h4>
                    <p class="exp-desc">
                        ระบบพิมพ์ใบเสร็จ 80mm พร้อมรหัสออเดอร์ วันที่ ยอดส่วนลด และรายละเอียดสินค้าโปร่งใส ตรวจสอบย้อนกลับได้ทุกบิล
                    </p>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer style="border-top: 1px solid var(--border-color); background: #07080a; padding: 40px 20px; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
        <div style="max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 1.4rem;">🌿</span>
                <span style="font-weight: 700; color: #ffffff; font-size: 1.1rem; letter-spacing: 0.05em;">CHILL42x DISPENSARY</span>
            </div>
            <p style="max-width: 650px; line-height: 1.6; margin: 0; color: #9ca3af;">
                ⚠️ ประกาศเตือน: ผลิตภัณฑ์กัญชาจำหน่ายเฉพาะบุคคลที่มีอายุ 20 ปีบริบูรณ์ขึ้นไป ห้ามจำหน่ายแก่สตรีมีครรภ์และสตรีให้นมบุตร เพื่อความปลอดภัยและการใช้ประโยชน์ตามคำแนะนำของผู้เชี่ยวชาญ
            </p>
            <div style="display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; margin-top: 8px;">
                <a href="index.php" style="color: var(--text-secondary); text-decoration: none;">🏠 หน้าแรก</a>
                <a href="pos.php" style="color: var(--text-secondary); text-decoration: none;">🛒 จุดขายหน้าร้าน POS</a>
                <a href="admin/product-list.php" style="color: var(--text-secondary); text-decoration: none;">📦 คลังสินค้า</a>
                <a href="login.php" style="color: var(--text-secondary); text-decoration: none;">🔐 เข้าสู่ระบบ</a>
            </div>
            <div style="font-size: 0.8rem; color: #6b7280; margin-top: 10px;">
                © 2026 CHILL42x Dispensary POS Terminal. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        function filterCatalog(category, buttonEl) {
            // Update active pill
            document.querySelectorAll('.catalog-pill').forEach(btn => btn.classList.remove('active'));
            if (buttonEl) buttonEl.classList.add('active');

            // Filter cards
            const cards = document.querySelectorAll('.showcase-card');
            cards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                if (category === 'all' || cardCat === category) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // ตรวจสอบสิทธิ์ลูกค้าหน้าร้าน (ชื่อ, อายุ, เพศ, อายุ 20+)
        function checkStorefrontEligibility() {
            const name = (document.getElementById('sf_customer_name').value || '').trim();
            const age = parseInt(document.getElementById('sf_customer_age').value || 0, 10);
            const gender = document.getElementById('sf_customer_gender').value;
            const resBox = document.getElementById('sfResultBox');

            if (!name || isNaN(age) || age <= 0 || !gender) {
                alert('กรุณากรอกข้อมูล ชื่อ, อายุ, และเพศ ให้ครบถ้วน');
                return;
            }

            resBox.style.display = 'block';

            if (age >= 20) {
                // อายุ 20 ปีขึ้นไป: ผ่านเกณฑ์ สามารถซื้อได้
                resBox.style.background = 'rgba(34, 197, 94, 0.15)';
                resBox.style.border = '1px solid rgba(34, 197, 94, 0.45)';
                resBox.style.color = '#ffffff';
                resBox.innerHTML = `
                    <div style="display: flex; align-items: flex-start; gap: 14px;">
                        <div style="font-size: 2rem; line-height: 1;">✅</div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 6px 0; color: #4ade80; font-size: 1.15rem;">ผ่านเกณฑ์การตรวจสอบสิทธิ์ (อายุ ${age} ปี)</h4>
                            <p style="margin: 0 0 12px 0; color: #d1d5db; font-size: 0.95rem; line-height: 1.5;">
                                ยินดีต้อนรับคุณ <strong>${name}</strong> (อายุ ${age} ปี, เพศ: ${gender}) ท่านผ่านเกณฑ์อายุ 20 ปีขึ้นไป สามารถซื้อผลิตภัณฑ์สมุนไพรกัญชาได้อย่างถูกต้องตามกฎหมาย
                            </p>
                            <a href="pos.php" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                                <span>🛒</span> ดำเนินการสั่งซื้อที่จุดขาย POS ➔
                            </a>
                        </div>
                    </div>
                `;
            } else {
                // อายุไม่ถึง 20 ปี: ไม่สามารถซื้อได้
                resBox.style.background = 'rgba(239, 68, 68, 0.18)';
                resBox.style.border = '1px solid rgba(239, 68, 68, 0.5)';
                resBox.style.color = '#ffffff';
                resBox.innerHTML = `
                    <div style="display: flex; align-items: flex-start; gap: 14px;">
                        <div style="font-size: 2rem; line-height: 1;">❌</div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 6px 0; color: #f87171; font-size: 1.15rem;">ไม่สามารถซื้อได้! (อายุต่ำกว่า 20 ปี)</h4>
                            <p style="margin: 0; color: #fca5a5; font-size: 0.95rem; line-height: 1.6;">
                                ขออภัยคุณ <strong>${name}</strong> (อายุ ${age} ปี, เพศ: ${gender}) <strong>ระบบไม่สามารถจำหน่ายสินค้าให้ได้</strong> เนื่องจากผู้ซื้อต้องมีอายุตั้งแต่ <strong>20 ปีบริบูรณ์ขึ้นไป</strong> ตาม พ.ร.บ. คุ้มครองและส่งเสริมภูมิปัญญาการแพทย์แผนไทย (กัญชาเป็นสมุนไพรควบคุม)
                            </p>
                        </div>
                    </div>
                `;
                alert(`❌ ไม่สามารถซื้อได้!\n\nขออภัยคุณ ${name} (อายุ ${age} ปี, เพศ: ${gender})\n\nระบบไม่สามารถจำหน่ายสินค้าให้ได้ เนื่องจากผู้ซื้อต้องมีอายุตั้งแต่ 20 ปีขึ้นไปตามกฎหมาย`);
            }
        }
    </script>
</body>
</html>

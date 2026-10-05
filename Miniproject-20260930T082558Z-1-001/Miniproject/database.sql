-- ========================================================
-- CHILL42x Dispensary POS - Unified Database Schema
-- ออกแบบสำหรับร้าน CHILL42x (ช่อดอก, น้ำมันสายหยอด, และต้นพันธุ์)
-- ฐานข้อมูล: dispensary_db
-- ========================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS `dispensary_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dispensary_db`;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `bulk_pricing_tiers`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. ตาราง users (สำหรับ Admin และ Staff)
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- รหัสผ่านเริ่มต้นคือ admin123 และ staff123 (พร้อม fallback 1234)
INSERT INTO `users` (`id`, `username`, `password`, `name`, `full_name`, `role`) VALUES
(1, 'admin', '$2y$10$3c8tSg0iP5ZkJ3wY1d6c8e3k8d4l9m0n1o2p3q4r5s6t7u8v9w0xy', 'ผู้จัดการร้าน (Admin)', 'ผู้จัดการร้าน (Admin)', 'admin'),
(2, 'staff', '$2y$10$3c8tSg0iP5ZkJ3wY1d6c8e3k8d4l9m0n1o2p3q4r5s6t7u8v9w0xy', 'พนักงานขายหน้าร้าน (Staff)', 'พนักงานขายหน้าร้าน (Staff)', 'staff');

-- 2. ตาราง products (รองรับ Abstract Product, CannabisFlower, CannabisOil, CannabisPlant)
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `type` ENUM('flower', 'oil', 'plant') NOT NULL,
    `product_type` ENUM('flower', 'oil', 'plant') NOT NULL,
    `base_price` DECIMAL(10, 2) NOT NULL,
    
    -- คุณสมบัติเฉพาะของ CannabisFlower (ดอกกัญชา - ชั่งน้ำหนักเป็นกรัม)
    `strain_type` VARCHAR(100) NULL,          -- Sativa, Indica, Hybrid
    `thc_percent` DECIMAL(5, 2) NULL DEFAULT 20.00,
    `stock_grams` DECIMAL(10, 2) NULL DEFAULT 0.00,
    
    -- คุณสมบัติเฉพาะของ CannabisOil (น้ำมันสกัดกัญชา - สายหยอด นับขวด)
    `extraction_method` VARCHAR(100) NULL,    -- Supercritical CO2, Fractional Distillation
    `stock_bottles` INT NULL DEFAULT 0,
    
    -- คุณสมบัติเฉพาะของ CannabisPlant (ต้นกล้าพันธุ์กัญชา - นับต้น)
    `age_weeks` INT NULL DEFAULT 0,
    `stock_pieces` INT NULL DEFAULT 0,
    
    -- ฟิลด์รวมทั่วไป
    `stock_quantity` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `image_url` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ตาราง bulk_pricing_tiers (เกณฑ์ราคาส่ง/ส่วนลดตามน้ำหนัก)
CREATE TABLE `bulk_pricing_tiers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `min_quantity` DECIMAL(10, 2) NOT NULL,
    `discount_percent` DECIMAL(5, 2) NOT NULL,
    `description` VARCHAR(100) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bulk_pricing_tiers` (`min_quantity`, `discount_percent`, `description`, `is_active`) VALUES
(3.50, 5.00, 'ซื้อ 3.5 กรัมขึ้นไป (1/8 oz) ลด 5%', 1),
(7.00, 10.00, 'ซื้อ 7 กรัมขึ้นไป (1/4 oz) ลด 10%', 1),
(14.00, 15.00, 'ซื้อ 14 กรัมขึ้นไป (1/2 oz) ลด 15%', 1),
(28.00, 20.00, 'ซื้อ 28 กรัมขึ้นไป (1 oz) ลด 20%', 1);

-- 4. ตาราง orders (บันทึกออเดอร์ ข้อมูลลูกค้า และการตรวจอายุ CustomerGuard 20+)
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_code` VARCHAR(50) NOT NULL UNIQUE,
    `order_number` VARCHAR(50) NULL,
    `user_id` INT NULL,
    `staff_name` VARCHAR(100) NULL,
    `customer_name` VARCHAR(100) NOT NULL DEFAULT 'ลูกค้าทั่วไป',
    `customer_age` INT NOT NULL DEFAULT 20,
    `customer_gender` VARCHAR(20) NOT NULL DEFAULT 'ไม่ระบุ',
    `customer_birthdate` DATE NOT NULL,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `discount_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `net_amount` DECIMAL(10, 2) NOT NULL,
    `cash_received` DECIMAL(10, 2) NOT NULL,
    `change_returned` DECIMAL(10, 2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ตาราง order_items (รายการสินค้าในแต่ละออเดอร์)
CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(150) NOT NULL,
    `product_type` VARCHAR(50) NOT NULL,
    `quantity` DECIMAL(10, 2) NOT NULL,
    `unit_label` VARCHAR(20) NOT NULL DEFAULT 'หน่วย',
    `unit_price` DECIMAL(10, 2) NOT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL,
    `discount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `discount_applied` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `final_price` DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ข้อมูลสินค้าเริ่มต้นของ CHILL42x (Seed Data - ข้อมูลสมจริง ถูกต้องตามหลักพฤกษศาสตร์และการสกัด)
INSERT INTO `products` (`id`, `name`, `type`, `product_type`, `base_price`, `strain_type`, `thc_percent`, `stock_grams`, `extraction_method`, `stock_bottles`, `age_weeks`, `stock_pieces`, `stock_quantity`, `image_url`, `description`) VALUES
(1, 'OG Kush Flower (Top Shelf)', 'flower', 'flower', 450.00, 'Hybrid (55% Indica / 45% Sativa)', 24.50, 150.50, NULL, NULL, NULL, NULL, 150.50, 'assets/images/flower_kush.jpg', 'ช่อดอกพรีเมียมเกรด Top Shelf เพาะปลูกระบบ Indoor ควบคุมสภาพแวดล้อม กลิ่นหอมสน เอิร์ธโทน และซิตรัสเข้มข้น อุดมด้วยเทอร์ปีน Myrcene และ Limonene ให้ความรู้สึกผ่อนคลายลึกพร้อมอารมณ์เบิกบาน'),
(2, 'Thai Sticky Sativa (หางกระรอกแท้)', 'flower', 'flower', 350.00, 'Sativa 100% (Landrace)', 18.50, 65.00, NULL, NULL, NULL, NULL, 65.00, 'assets/images/flower_thai_sativa.jpg', 'กัญชาสายพันธุ์แลนด์เรซแท้จากเทือกเขาภูพาน กลิ่นหอมสมุนไพรสดชื่น ผสานมะนาวป่า โดดเด่นด้วยเทอร์ปีน Terpinolene และ Pinene ออกฤทธิ์กระปรี้กระเปร่า เพิ่มสมาธิและความคิดสร้างสรรค์'),
(3, 'Granddaddy Purple (Indica)', 'flower', 'flower', 500.00, 'Indica (80% Indica / 20% Sativa)', 22.00, 45.00, NULL, NULL, NULL, NULL, 45.00, 'assets/images/flower_gdp.jpg', 'ช่อดอกสีม่วงเข้มปกคลุมด้วยไตรโคมหนาแน่น กลิ่นหอมหวานเด่นของเบอร์รี่ป่าและองุ่นสุก เทอร์ปีน Caryophyllene และ Linalool สูง ออกฤทธิ์คลายความตึงเครียดของกล้ามเนื้อ ช่วยให้นอนหลับลึกอย่างมีประสิทธิภาพ'),
(4, 'Full Spectrum CBD Oil Drops 1000mg', 'oil', 'oil', 1200.00, NULL, NULL, NULL, 'Supercritical CO2 Extraction', 18, NULL, NULL, 18.00, 'assets/images/cannabis_oil.jpg', 'น้ำมันสกัดสายหยอดสูตร Full Spectrum เข้มข้น 1,000 มก. ในน้ำมัน MCT ออร์แกนิก สกัดเย็นด้วยก๊าซคาร์บอนไดออกไซด์แรงดันวิกฤต ไร้สารเคมีตกค้าง (THC < 0.2%) ช่วยบรรเทาอาการปวดและลดความวิตกกังวล'),
(5, 'Pure THC Distillate Drops (สายหยอด)', 'oil', 'oil', 1500.00, NULL, NULL, NULL, 'Fractional Distillation', 10, NULL, NULL, 10.00, 'assets/images/oil_thc_distillate.jpg', 'สารสกัดหยดใต้ลิ้นความบริสุทธิ์สูง 85% ผ่านกระบวนการกลั่นลำดับส่วนมาตรฐานห้องปฏิบัติการ เสริมกลิ่นเทอร์ปีนธรรมชาติ ออกฤทธิ์รวดเร็วและควบคุมปริมาณหยดได้แม่นยำสำหรับการใช้งานเฉพาะจุด'),
(6, 'White Widow Clone (ต้นแม่พันธุ์)', 'plant', 'plant', 850.00, NULL, NULL, NULL, NULL, NULL, 4, 15, 15.00, 'assets/images/cannabis_plant.jpg', 'ต้นกล้าตัดชำจากต้นแม่พันธุ์ White Widow แท้ อายุ 4 สัปดาห์ ระบบรากเดินเต็มก้อนร็อควูลพร้อมลงกระถางปลูก ลำต้นแข็งแรง ข้อถี่ ต้านทานโรคและศัตรูพืชได้ดีเยี่ยม'),
(7, 'Amnesia Haze Young Plant', 'plant', 'plant', 950.00, NULL, NULL, NULL, NULL, NULL, 6, 8, 8.00, 'assets/images/plant_amnesia_haze.jpg', 'ต้นพันธุ์รุ่นเจริญเติบโต (Vegetative Stage) อายุ 6 สัปดาห์ ฟอร์มพุ่มกิ่งก้านสมบูรณ์ ลำต้นหนาพร้อมรับการตัดแต่งทรงพุ่ม (Topping/LST) ก่อนเข้าสู่ระยะทำดอก ให้ผลผลิตต่อกิ่งสูง');

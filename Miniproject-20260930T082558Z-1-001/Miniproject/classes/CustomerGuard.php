<?php
/**
 * CustomerGuard Class (สอดคล้องกับ Class Diagram รูปที่ 1 และ Sequence Diagram รูปที่ 5)
 * ทำหน้าที่เป็น Guard Pattern ในการตรวจสอบคุณสมบัติของผู้ซื้อตามกฎหมาย
 * พระราชบัญญัติคุ้มครองและส่งเสริมภูมิปัญญาการแพทย์แผนไทย (กัญชาเป็นสมุนไพรควบคุม)
 * 
 * [ข้อกำหนดตามกฎหมายและ Flowchart รูปที่ 3]
 * - ผู้ซื้อต้องมีอายุไม่ต่ำกว่า 20 ปีบริบูรณ์ (คำนวณจากวันเดือนปีเกิดจริง)
 * - หากอายุ < 20 ปี: ระบบจะปฏิเสธการขายทันที และไม่อนุญาตให้เปิดบิล
 */

class CustomerGuard {
    // อายุขั้นต่ำที่กฎหมายอนุญาตให้ซื้อได้ (20 ปีบริบูรณ์)
    public const MINIMUM_AGE = 20;

    /**
     * +verifyAge(String birthdate) : bool ตาม Class Diagram รูปที่ 1
     * ตรวจสอบว่าลูกค้ามีอายุครบ 20 ปีบริบูรณ์หรือไม่
     * 
     * @param string $birthdate วันเกิดในรูปแบบ 'YYYY-MM-DD'
     * @return bool คืนค่า true หากผ่านเกณฑ์ (อายุ >= 20 ปี), คืนค่า false หากอายุไม่ถึงหรือวันที่ไม่ถูกต้อง
     */
    public static function verifyAge(string $birthdate): bool {
        if (empty(trim($birthdate))) {
            return false;
        }

        try {
            $dob = new DateTime($birthdate);
            $today = new DateTime('today');

            // หากระบุวันที่ในอนาคต (ข้อมูลไม่ถูกต้อง)
            if ($dob > $today) {
                return false;
            }

            // คำนวณอายุเต็มปี (เทียบเดือนและวันเกิดจริง)
            $age = $dob->diff($today)->y;
            return $age >= self::MINIMUM_AGE;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Method สำหรับเรียกใช้งานแบบ Instance Object
     */
    public function check(string $birthdate): bool {
        return self::verifyAge($birthdate);
    }

    /**
     * คำนวณอายุจริงเป็นจำนวนปีบริบูรณ์
     * 
     * @param string $birthdate วันเดือนปีเกิด
     * @return int อายุ (ปี)
     */
    public static function getAge(string $birthdate): int {
        try {
            $dob = new DateTime($birthdate);
            $today = new DateTime('today');
            return $dob->diff($today)->y;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Alias สำหรับคำนวณอายุ (ความเข้ากันได้กับ pos.php)
     */
    public static function calculateAge(string $birthdate): int {
        return self::getAge($birthdate);
    }

    /**
     * คืนค่าวันเกิดสูงสุดที่อนุญาตให้ซื้อได้ (สำหรับตั้งเป็น max ของ input date ในหน้าเว็บ)
     */
    public static function getMaxAllowedBirthdate(): string {
        $date = new DateTime('today');
        $date->modify('-' . self::MINIMUM_AGE . ' years');
        return $date->format('Y-m-d');
    }

    /**
     * ตรวจสอบอายุจากตัวเลขอายุโดยตรง (ต้องอายุ 20 ปีขึ้นไป)
     * 
     * @param int $age อายุของลูกค้า
     * @return bool
     */
    public static function verifyAgeNumber(int $age): bool {
        return $age >= self::MINIMUM_AGE;
    }

    /**
     * ตรวจสอบออบเจกต์ลูกค้า Customer ว่าสามารถซื้อได้หรือไม่
     */
    public static function verifyCustomer($customer): bool {
        if (is_object($customer) && method_exists($customer, 'canPurchase')) {
            return $customer->canPurchase();
        }
        if (is_numeric($customer)) {
            return self::verifyAgeNumber((int)$customer);
        }
        if (is_string($customer)) {
            return self::verifyAge($customer);
        }
        return false;
    }
}

<?php
/**
 * Customer Class
 * จัดการข้อมูลลูกค้า (ชื่อ, อายุ, เพศ, วันเกิด) และตรวจสอบคุณสมบัติตามกฎหมาย
 * เงื่อนไขตาม พ.ร.บ. คุ้มครองและส่งเสริมภูมิปัญญาการแพทย์แผนไทย:
 * - ลูกค้าต้องมีอายุ 20 ปีขึ้นไป จึงจะอนุญาตให้ซื้อสินค้าได้
 * - หากอายุไม่ถึง 20 ปี ระบบจะแจ้งเตือนว่าไม่สามารถซื้อได้
 */

require_once __DIR__ . '/CustomerGuard.php';

class Customer {
    public const MINIMUM_AGE = 20;

    private string $name;
    private int $age;
    private string $gender;
    private ?string $birthdate;

    /**
     * Constructor สำหรับสร้างออบเจกต์ลูกค้า
     * 
     * @param string $name ชื่อลูกค้า
     * @param int $age อายุของลูกค้า (ปี)
     * @param string $gender เพศของลูกค้า ('ชาย', 'หญิง', 'อื่นๆ' หรือ 'ไม่ระบุ')
     * @param string|null $birthdate วันเกิดลูกค้า (YYYY-MM-DD) หรือ null
     */
    public function __construct(string $name = 'ลูกค้าทั่วไป', int $age = 20, string $gender = 'ไม่ระบุ', ?string $birthdate = null) {
        $this->name = trim($name) !== '' ? trim($name) : 'ลูกค้าทั่วไป';
        $this->age = (int)$age;
        $this->gender = trim($gender) !== '' ? trim($gender) : 'ไม่ระบุ';
        
        if (!empty($birthdate)) {
            $this->birthdate = $birthdate;
            // หากระบุวันเกิด ให้คำนวณอายุที่แท้จริงหากอายุไม่ได้ระบุไว้
            if ($this->age <= 0) {
                $this->age = CustomerGuard::calculateAge($birthdate);
            }
        } else {
            // หากไม่ได้ระบุวันเกิด ให้จำลองวันเกิดย้อนหลังตามอายุ
            $this->birthdate = date('Y-m-d', strtotime("-{$this->age} years"));
        }
    }

    /**
     * Factory method สร้าง Customer จากวันเกิด
     */
    public static function createFromBirthdate(string $name, string $birthdate, string $gender = 'ไม่ระบุ'): self {
        $age = CustomerGuard::calculateAge($birthdate);
        return new self($name, $age, $gender, $birthdate);
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = trim($name) !== '' ? trim($name) : 'ลูกค้าทั่วไป';
    }

    public function getAge(): int {
        return $this->age;
    }

    public function setAge(int $age): void {
        $this->age = max(0, $age);
        if (empty($this->birthdate)) {
            $this->birthdate = date('Y-m-d', strtotime("-{$this->age} years"));
        }
    }

    public function getGender(): string {
        return $this->gender;
    }

    public function setGender(string $gender): void {
        $this->gender = trim($gender) !== '' ? trim($gender) : 'ไม่ระบุ';
    }

    public function getBirthdate(): ?string {
        return $this->birthdate;
    }

    public function setBirthdate(string $birthdate): void {
        $this->birthdate = $birthdate;
        $this->age = CustomerGuard::calculateAge($birthdate);
    }

    /**
     * ตรวจสอบว่าลูกค้าสามารถซื้อสินค้าได้หรือไม่
     * ต้องมีอายุ 20 ปีขึ้นไปตามกฎหมาย
     * 
     * @return bool true หากอายุ >= 20, false หากอายุ < 20
     */
    public function canPurchase(): bool {
        return $this->age >= self::MINIMUM_AGE;
    }

    /**
     * คืนค่าข้อความแจ้งเตือนสถานะการซื้อ
     */
    public function getEligibilityStatus(): array {
        if ($this->canPurchase()) {
            return [
                'eligible' => true,
                'status'   => 'success',
                'message'  => "ลูกค้า '{$this->name}' (อายุ {$this->age} ปี, เพศ {$this->gender}) ผ่านเกณฑ์อายุ 20 ปีขึ้นไป สามารถซื้อได้"
            ];
        }

        return [
            'eligible' => false,
            'status'   => 'error',
            'message'  => "ไม่สามารถซื้อได้! ลูกค้าชื่อ '{$this->name}' อายุ {$this->age} ปี (ต้องมีอายุ 20 ปีขึ้นไปตาม พ.ร.บ. คุ้มครองและส่งเสริมภูมิปัญญาฯ)"
        ];
    }

    /**
     * แปลงข้อมูลลูกค้าเป็น Array
     */
    public function toArray(): array {
        return [
            'name'         => $this->name,
            'age'          => $this->age,
            'gender'       => $this->gender,
            'birthdate'    => $this->birthdate,
            'can_purchase' => $this->canPurchase(),
        ];
    }
}

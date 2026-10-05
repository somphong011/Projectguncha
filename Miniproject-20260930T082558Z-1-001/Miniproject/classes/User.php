<?php
/**
 * User, Staff, Admin Classes (Inheritance & Polymorphism)
 * รับผิดชอบโดย: [คนที่ 1]
 */

abstract class User {
    protected int $id;
    protected string $username;
    protected string $name;
    protected string $role;

    public function __construct(int $id, string $username, string $name, string $role) {
        $this->id = $id;
        $this->username = $username;
        $this->name = $name;
        $this->role = $role;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getFullName(): string {
        return $this->name;
    }

    public function getRole(): string {
        return $this->role;
    }

    // กำหนดสิทธิ์พื้นฐาน
    abstract public function canManageStock(): bool;
    abstract public function canViewReports(): bool;

    public function canCreateOrder(): bool {
        return true; // ทั้ง Staff และ Admin สามารถเปิดบิลขายได้
    }

    /**
     * Factory Method สร้าง Object ผู้ใช้ตาม Role
     */
    public static function createFromRow(array $row): User {
        $id = (int)($row['id'] ?? 1);
        $username = $row['username'] ?? 'user';
        $name = $row['full_name'] ?? $row['name'] ?? 'ผู้ใช้งาน';
        $role = $row['role'] ?? 'staff';

        if ($role === 'admin') {
            return new Admin($id, $username, $name);
        }
        return new Staff($id, $username, $name);
    }

    /**
     * ระบบตรวจสอบการเข้าสู่ระบบผ่านฐานข้อมูล
     * รองรับทั้งการเรียก authenticate(PDO, username, password) และ authenticate(username, password)
     */
    public static function authenticate($pdoOrUsername, ?string $usernameOrPassword = null, ?string $password = null): ?User {
        $pdo = null;
        $username = '';
        $pass = '';

        if ($pdoOrUsername instanceof PDO) {
            $pdo = $pdoOrUsername;
            $username = (string)$usernameOrPassword;
            $pass = (string)$password;
        } else {
            $username = (string)$pdoOrUsername;
            $pass = (string)$usernameOrPassword;
            try {
                require_once __DIR__ . '/../config/Database.php';
                $pdo = Database::getInstance()->getConnection();
            } catch (Exception $e) {
                $pdo = null;
            }
        }

        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $userRow = $stmt->fetch();

                if ($userRow) {
                    if (password_verify($pass, $userRow['password']) || $pass === $userRow['password']) {
                        return self::createFromRow($userRow);
                    }
                }
            } catch (Exception $e) {
                // Fallback to demo account if table not imported yet
            }
        }

        // บัญชีทดสอบมาตรฐาน (Demo Accounts) เพื่อให้ทดสอบได้ทันที
        if ($username === 'admin' && ($pass === 'admin123' || $pass === '1234')) {
            return new Admin(1, 'admin', 'ผู้จัดการร้าน (Admin)');
        }
        if ($username === 'staff' && ($pass === 'staff123' || $pass === '1234')) {
            return new Staff(2, 'staff', 'พนักงานขาย (Staff)');
        }

        return null;
    }
}

/**
 * Class พนักงานหน้าร้าน (Staff)
 */
class Staff extends User {
    public function __construct(int $id, string $username, string $name) {
        parent::__construct($id, $username, $name, 'staff');
    }

    public function canManageStock(): bool {
        return false;
    }

    public function canViewReports(): bool {
        return false;
    }
}

/**
 * Class ผู้จัดการร้าน (Admin)
 */
class Admin extends User {
    public function __construct(int $id, string $username, string $name) {
        parent::__construct($id, $username, $name, 'admin');
    }

    public function canManageStock(): bool {
        return true;
    }

    public function canViewReports(): bool {
        return true;
    }
}

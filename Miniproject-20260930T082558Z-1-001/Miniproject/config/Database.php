<?php
/**
 * Database Connection (PDO Singleton Pattern)
 * รับผิดชอบโดย: [คนที่ 1]
 * 
 * รองรับทั้ง:
 * - Database::getInstance()->getConnection()
 * - Database::getInstance()->prepare(...) / query(...)
 */

class Database {
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private static string $host = 'localhost';
    private static string $dbName = 'dispensary_db';
    private static string $username = 'root';
    private static string $password = '';
    private static string $charset = 'utf8mb4';

    // ป้องกันการ new instance จากภายนอก (Singleton)
    private function __construct() {
        $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbName . ";charset=" . self::$charset;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, self::$username, self::$password, $options);
        } catch (PDOException $e) {
            // ไม่ใช้ die() เพื่อให้ระบบทำงานต่อได้ในโหมด In-Memory / Mock
            $this->pdo = null;
        }
    }

    // ป้องกันการ clone instance (Singleton)
    private function __clone() {}

    /**
     * ดึง Object Singleton ของคลาส Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * ดึง Object การเชื่อมต่อ PDO
     */
    public function getConnection(): ?PDO {
        return $this->pdo;
    }

    /**
     * ส่งต่อ Method เรียกตรงเข้า PDO (เช่น prepare, query, beginTransaction)
     */
    public function __call(string $name, array $arguments) {
        if ($this->pdo !== null && method_exists($this->pdo, $name)) {
            return call_user_func_array([$this->pdo, $name], $arguments);
        }
        return null;
    }
}

<?php
/**
 * Authentication & Session Helper
 * ระบบจัดการ Session และสิทธิ์การเข้าใช้งานระบบ CHILL42x Dispensary POS
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * ตรวจสอบว่าผู้ใช้มีสิทธิ์ระดับ Admin หรือไม่
 */
function isAdmin(): bool {
    return isLoggedIn() && (($_SESSION['role'] ?? '') === 'admin');
}

/**
 * ดึงข้อมูลผู้ใช้งานปัจจุบัน
 */
function getCurrentUser(): array {
    if (isLoggedIn()) {
        return [
            'id'        => (int)$_SESSION['user_id'],
            'username'  => $_SESSION['username'] ?? 'user',
            'full_name' => $_SESSION['full_name'] ?? 'ผู้ใช้งาน',
            'role'      => $_SESSION['role'] ?? 'staff',
        ];
    }

    // Default mock user สำหรับอำนวยความสะดวกในการทดสอบหน้าร้าน
    return [
        'id'        => 2,
        'username'  => 'staff',
        'full_name' => 'พนักงานขายหน้าร้าน (Staff)',
        'role'      => 'staff',
    ];
}

/**
 * บังคับให้ต้องล็อกอินก่อนเข้าหน้านั้น
 */
function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'กรุณาเข้าสู่ระบบก่อนทำรายการ';
        header("Location: {$redirect}");
        exit();
    }
}

/**
 * บังคับให้เฉพาะ Admin เท่านั้นที่เข้าถึงได้
 */
function requireAdmin(string $redirect = '../login.php'): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'กรุณาเข้าสู่ระบบก่อนทำรายการ';
        header("Location: {$redirect}");
        exit();
    }

    if (!isAdmin()) {
        $_SESSION['flash_error'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (เฉพาะผู้จัดการ Admin เท่านั้น)';
        // ถ้าอยู่ในโฟลเดอร์ admin ให้ถอยไป pos.php
        $target = (strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false) ? '../pos.php' : 'pos.php';
        header("Location: {$target}");
        exit();
    }
}

/**
 * ล็อกเอาท์ออกจากระบบ
 */
function logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

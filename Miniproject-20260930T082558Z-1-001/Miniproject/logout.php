<?php
/**
 * Logout Endpoint
 * ระบบ CHILL42x Dispensary
 */
require_once __DIR__ . '/includes/auth.php';
logout();
header('Location: login.php');
exit();

<?php
/**
 * Global Navigation Header (Mostudio Dark Luxury Aesthetic)
 * รองรับทั้งการเรียกจาก root และโฟลเดอร์ admin/ พร้อม Mobile Responsive Drawer
 */
$navBase = (strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false) ? '../' : '';
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$user = getCurrentUser();
$isAdminUser = isAdmin();
?>
<!-- ม่านดำ Backdrop สำหรับเมนูมือถือ -->
<div class="drawer-backdrop" id="navDrawerBackdrop" onclick="toggleNavDrawer()"></div>

<header class="mostudio-navbar">
    <div class="navbar-container">
        <!-- ปุ่ม Hamburger บนหน้าจอมือถือ -->
        <button type="button" class="navbar-mobile-toggle" onclick="toggleNavDrawer()" aria-label="เปิดเมนู">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Brand Logo -->
        <a href="<?= $navBase ?>index.php" class="navbar-brand">
            <span class="brand-symbol">🌿</span>
            <div class="brand-text-group">
                <span class="brand-title">CHILL42x<span class="brand-highlight">.shop</span></span>
                <span class="brand-subtitle">Craft Dispensary & Oil Drops</span>
            </div>
        </a>

        <!-- Desktop Navigation Links -->
        <nav class="navbar-menu">
            <a href="<?= $navBase ?>index.php" class="nav-link <?= ($currentScript === 'index.php') ? 'active' : '' ?>">
                <span>🏠</span> หน้าแรก
            </a>
            <a href="<?= $navBase ?>pos.php" class="nav-link <?= ($currentScript === 'pos.php') ? 'active' : '' ?>">
                <span>🛒</span> จุดขาย POS
            </a>
            <a href="<?= $navBase ?>admin/product-list.php" class="nav-link <?= ($currentScript === 'product-list.php') ? 'active' : '' ?>">
                <span>📦</span> คลังสินค้า
            </a>
            <?php if ($isAdminUser): ?>
                <a href="<?= $navBase ?>admin/product-add.php" class="nav-link <?= ($currentScript === 'product-add.php') ? 'active' : '' ?>">
                    <span>➕</span> เพิ่มสินค้า
                </a>
            <?php endif; ?>
        </nav>

        <!-- User Profile & Action -->
        <div class="navbar-user-actions">
            <?php if (isLoggedIn()): ?>
                <div class="user-pill">
                    <div class="user-avatar-circle"><?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?></div>
                    <div class="user-meta">
                        <span class="user-display-name"><?= htmlspecialchars($user['full_name'] ?? 'ผู้ใช้') ?></span>
                        <span class="user-role-badge <?= (($user['role'] ?? '') === 'admin') ? 'badge-admin' : 'badge-staff' ?>">
                            <?= (($user['role'] ?? '') === 'admin') ? 'ผู้จัดการ (Admin)' : 'พนักงาน (Staff)' ?>
                        </span>
                    </div>
                </div>
                <a href="<?= $navBase ?>logout.php" class="btn btn-logout" title="ออกจากระบบ">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>ออก</span>
                </a>
            <?php else: ?>
                <a href="<?= $navBase ?>login.php" class="btn btn-primary btn-sm">
                    เข้าสู่ระบบ
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Mobile Slide-In Drawer -->
<div class="mobile-nav-drawer" id="mobileNavDrawer">
    <div class="mobile-drawer-header">
        <div class="brand-text-group">
            <span class="brand-title">CHILL42x<span class="brand-highlight">.shop</span></span>
            <span class="brand-subtitle">Craft Dispensary</span>
        </div>
        <button type="button" class="drawer-close-btn" onclick="toggleNavDrawer()">✕</button>
    </div>

    <div class="mobile-drawer-body">
        <?php if (isLoggedIn()): ?>
            <div class="drawer-user-card">
                <div class="user-avatar-circle"><?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?></div>
                <div class="user-meta">
                    <span class="user-display-name"><?= htmlspecialchars($user['full_name'] ?? 'ผู้ใช้') ?></span>
                    <span class="user-role-badge <?= (($user['role'] ?? '') === 'admin') ? 'badge-admin' : 'badge-staff' ?>">
                        <?= (($user['role'] ?? '') === 'admin') ? 'ผู้จัดการ (Admin)' : 'พนักงาน (Staff)' ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <nav class="mobile-drawer-links">
            <a href="<?= $navBase ?>index.php" class="drawer-link <?= ($currentScript === 'index.php') ? 'active' : '' ?>">
                <span>🏠</span> หน้าแรก (Overview)
            </a>
            <a href="<?= $navBase ?>pos.php" class="drawer-link <?= ($currentScript === 'pos.php') ? 'active' : '' ?>">
                <span>🛒</span> จุดขายหน้าร้าน (POS Terminal)
            </a>
            <a href="<?= $navBase ?>admin/product-list.php" class="drawer-link <?= ($currentScript === 'product-list.php') ? 'active' : '' ?>">
                <span>📦</span> คลังสินค้า & สต็อก (Stock)
            </a>
            <?php if ($isAdminUser): ?>
                <a href="<?= $navBase ?>admin/product-add.php" class="drawer-link <?= ($currentScript === 'product-add.php') ? 'active' : '' ?>">
                    <span>➕</span> เพิ่มสินค้าใหม่ (Add Product)
                </a>
            <?php endif; ?>
            <?php if (isLoggedIn()): ?>
                <a href="<?= $navBase ?>logout.php" class="drawer-link drawer-link-logout">
                    <span>🚪</span> ออกจากระบบ (Logout)
                </a>
            <?php else: ?>
                <a href="<?= $navBase ?>login.php" class="drawer-link drawer-link-login">
                    <span>🔑</span> เข้าสู่ระบบ (Login)
                </a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<script>
    function toggleNavDrawer() {
        const drawer = document.getElementById('mobileNavDrawer');
        const backdrop = document.getElementById('navDrawerBackdrop');
        if (drawer && backdrop) {
            const isOpen = drawer.classList.toggle('open');
            backdrop.classList.toggle('active', isOpen);
        }
    }
</script>

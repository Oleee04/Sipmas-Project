<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <i class="bi bi-megaphone-fill"></i>
        </div>
        <div>
            <div class="brand-name">SIPMAS</div>
            <div class="brand-desc">Pengaduan Masyarakat</div>
        </div>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-label">Menu Utama</div>

        <a href="/sipmas/dashboard.php" class="sidebar-item <?= $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-grid-1x2"></i>
            <span>Dashboard</span>
        </a>

        <a href="/sipmas/pengaduan/tambah.php" class="sidebar-item <?= ($current_dir == 'pengaduan' && $current_page == 'tambah.php') ? 'active' : ''; ?>">
            <i class="bi bi-pencil-square"></i>
            <span>Buat Pengaduan</span>
        </a>

        <a href="/sipmas/pengaduan/daftar.php" class="sidebar-item <?= ($current_dir == 'pengaduan' && $current_page == 'daftar.php') ? 'active' : ''; ?>">
            <i class="bi bi-list-check"></i>
            <span>Pengaduan Saya</span>
        </a>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-label">Layanan</div>

        <a href="/sipmas/bantuan/panduan.php" class="sidebar-item <?= ($current_dir == 'bantuan' && $current_page == 'panduan.php') ? 'active' : ''; ?>">
            <i class="bi bi-question-circle"></i>
            <span>Bantuan</span>
        </a>

        <a href="/sipmas/profil/edit.php" class="sidebar-item <?= ($current_dir == 'profil') ? 'active' : ''; ?>">
            <i class="bi bi-person-circle"></i>
            <span>Profil</span>
        </a>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">
            <?= isset($_SESSION['nama']) ? strtoupper(substr($_SESSION['nama'], 0, 1)) : 'U'; ?>
        </div>

        <div class="user-info">
            <div class="user-name">
                <?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'User'; ?>
            </div>
            <div class="user-role">Masyarakat</div>
        </div>
    </div>

    <a href="/sipmas/logout.php" class="logout-btn">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </a>
</aside>
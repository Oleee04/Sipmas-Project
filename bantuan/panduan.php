<?php
require_once __DIR__ . "/../config/database.php";
/** @var mysqli $conn */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/header.php";

$allowed_pages = [
    "membuat_pengaduan" => [
        "title" => "Cara Membuat Pengaduan",
        "file" => __DIR__ . "/pages/membuat_pengaduan.php",
        "icon" => "bi-pencil-square"
    ],
    "mengunggah_bukti" => [
        "title" => "Mengunggah Bukti",
        "file" => __DIR__ . "/pages/mengunggah_bukti.php",
        "icon" => "bi-paperclip"
    ],
    "melihat_pengaduan" => [
        "title" => "Melihat Pengaduan Saya",
        "file" => __DIR__ . "/pages/melihat_pengaduan.php",
        "icon" => "bi-list-check"
    ],
    "mengelola_profil" => [
        "title" => "Mengelola Profil",
        "file" => __DIR__ . "/pages/mengelola_profil.php",
        "icon" => "bi-person-circle"
    ],
];

$error = "";
$topik = isset($_GET['topik']) ? $_GET['topik'] : "membuat_pengaduan";

if (!array_key_exists($topik, $allowed_pages)) {
    $topik = "membuat_pengaduan";
    $error = "Topik panduan tidak valid. Sistem menampilkan panduan utama.";
}

$selected_page = $allowed_pages[$topik];

$selected_file = realpath($selected_page['file']);
$pages_dir = realpath(__DIR__ . "/pages");

$file_aman = false;

if ($selected_file && $pages_dir) {
    $pages_dir = rtrim($pages_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (strncmp($selected_file, $pages_dir, strlen($pages_dir)) === 0) {
        $file_aman = true;
    }
}
?>

<div class="app-shell">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="topbar-title">Bantuan</h1>
                <p class="topbar-subtitle">Panduan penggunaan aplikasi SIPMAS</p>
            </div>
        </header>

        <section class="page">
            <div class="page-header">
                <h2 class="page-title">Panduan Penggunaan</h2>
                <p class="page-subtitle">
                    Pilih topik bantuan untuk melihat panduan penggunaan SIPMAS.
                </p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-warning rounded-4">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="clean-card p-4">
                        <h5 class="fw-bold mb-3">Topik Bantuan</h5>

                        <div class="d-grid gap-2">
                            <?php foreach ($allowed_pages as $key => $page): ?>
                                <a 
                                    href="panduan.php?topik=<?= urlencode($key); ?>" 
                                    class="quick-link <?= $topik === $key ? 'active' : ''; ?>"
                                >
                                    <i class="bi <?= htmlspecialchars($page['icon']); ?>"></i>
                                    <span><?= htmlspecialchars($page['title']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="clean-card p-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="stat-icon icon-blue">
                                <i class="bi <?= htmlspecialchars($selected_page['icon']); ?>"></i>
                            </div>

                            <div>
                                <h4 class="fw-bold mb-1">
                                    <?= htmlspecialchars($selected_page['title']); ?>
                                </h4>
                                <p class="text-muted mb-0">
                                    Informasi bantuan berdasarkan topik yang dipilih.
                                </p>
                            </div>
                        </div>

                        <hr>

                        <div class="panduan-content">
                            <?php
                            if ($file_aman && file_exists($selected_file)) {
                                include $selected_file;
                            } else {
                                echo '<div class="alert alert-danger rounded-4">File panduan tidak dapat ditampilkan.</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
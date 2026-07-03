<?php
require_once __DIR__ . "/config/database.php";
/** @var mysqli $conn */

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/header.php";

$user_id = (int) $_SESSION['user_id'];

function getTotalPengaduan(mysqli $conn, int $user_id, ?string $status = null): int
{
    if ($status === null) {
        $query = "SELECT COUNT(*) AS total FROM pengaduan WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    } else {
        $query = "SELECT COUNT(*) AS total FROM pengaduan WHERE user_id = ? AND status = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $status);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    return isset($data['total']) ? (int) $data['total'] : 0;
}

$total_pengaduan = getTotalPengaduan($conn, $user_id);
$total_dikirim = getTotalPengaduan($conn, $user_id, "Dikirim");
$total_diproses = getTotalPengaduan($conn, $user_id, "Diproses");
$total_selesai = getTotalPengaduan($conn, $user_id, "Selesai");

$query_terbaru = "SELECT id, kode_pengaduan, judul, kategori, status, created_at 
                  FROM pengaduan 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC 
                  LIMIT 5";

$stmt_terbaru = mysqli_prepare($conn, $query_terbaru);
mysqli_stmt_bind_param($stmt_terbaru, "i", $user_id);
mysqli_stmt_execute($stmt_terbaru);
$result_terbaru = mysqli_stmt_get_result($stmt_terbaru);

function badgeStatusDashboard(string $status): string
{
    $class = "status-dikirim";

    if ($status === "Diproses") {
        $class = "status-diproses";
    } elseif ($status === "Selesai") {
        $class = "status-selesai";
    } elseif ($status === "Ditolak") {
        $class = "status-ditolak";
    }

    return '<span class="badge-status ' . $class . '">' . htmlspecialchars($status) . '</span>';
}
?>

<div class="app-shell">
    <?php require_once __DIR__ . "/includes/sidebar.php"; ?>

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="topbar-title">Dashboard</h1>
                <p class="topbar-subtitle">
                    Selamat datang, <?= htmlspecialchars($_SESSION['nama']); ?>
                </p>
            </div>
        </header>

        <section class="page">
            <div class="page-header">
                <h2 class="page-title">Ringkasan Pengaduan</h2>
                <p class="page-subtitle">
                    Pantau jumlah dan perkembangan pengaduan yang sudah Anda kirim.
                </p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-inner">
                            <div>
                                <div class="stat-label">Total Pengaduan</div>
                                <div class="stat-number"><?= (int) $total_pengaduan; ?></div>
                            </div>

                            <div class="stat-icon icon-blue">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-inner">
                            <div>
                                <div class="stat-label">Dikirim</div>
                                <div class="stat-number"><?= (int) $total_dikirim; ?></div>
                            </div>

                            <div class="stat-icon icon-blue">
                                <i class="bi bi-send"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-inner">
                            <div>
                                <div class="stat-label">Diproses</div>
                                <div class="stat-number"><?= (int) $total_diproses; ?></div>
                            </div>

                            <div class="stat-icon icon-yellow">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-inner">
                            <div>
                                <div class="stat-label">Selesai</div>
                                <div class="stat-number"><?= (int) $total_selesai; ?></div>
                            </div>

                            <div class="stat-icon icon-green">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="clean-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Pengaduan Terbaru</h5>
                                <p class="text-muted mb-0">
                                    Lima pengaduan terakhir yang Anda kirim.
                                </p>
                            </div>

                            <a href="pengaduan/daftar.php" class="btn btn-outline-primary">
                                Lihat Semua
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Judul</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php if ($result_terbaru && mysqli_num_rows($result_terbaru) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result_terbaru)): ?>
                                            <tr>
                                                <td class="fw-semibold">
                                                    <?= htmlspecialchars($row['kode_pengaduan']); ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($row['judul']); ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($row['kategori']); ?>
                                                </td>

                                                <td>
                                                    <?= badgeStatusDashboard($row['status']); ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars(date("d M Y", strtotime($row['created_at']))); ?>
                                                </td>

                                                <td class="text-end">
                                                    <a href="pengaduan/detail.php?id=<?= (int) $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <div class="stat-icon icon-blue mx-auto mb-3">
                                                    <i class="bi bi-inbox"></i>
                                                </div>

                                                <h5 class="fw-bold">Belum ada pengaduan</h5>

                                                <p class="text-muted mb-3">
                                                    Anda belum membuat pengaduan apa pun.
                                                </p>

                                                <a href="pengaduan/tambah.php" class="btn btn-primary">
                                                    Buat Pengaduan
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="clean-card p-4 mb-4">
                        <h5 class="fw-bold mb-3">Akses Cepat</h5>

                        <div class="d-grid gap-3">
                            <a href="pengaduan/tambah.php" class="quick-link">
                                <i class="bi bi-pencil-square"></i>
                                <span>Buat Pengaduan Baru</span>
                            </a>

                            <a href="pengaduan/daftar.php" class="quick-link">
                                <i class="bi bi-list-check"></i>
                                <span>Pengaduan Saya</span>
                            </a>

                            <a href="bantuan/panduan.php" class="quick-link">
                                <i class="bi bi-question-circle"></i>
                                <span>Bantuan Penggunaan</span>
                            </a>

                            <a href="profil/edit.php" class="quick-link">
                                <i class="bi bi-person-circle"></i>
                                <span>Edit Profil</span>
                            </a>
                        </div>
                    </div>

                    <div class="clean-card p-4">
                        <h5 class="fw-bold mb-3">Informasi Status</h5>

                        <div class="d-flex gap-3 mb-3">
                            <span class="badge-status status-dikirim">Dikirim</span>
                            <div class="text-muted small">
                                Pengaduan sudah masuk ke sistem.
                            </div>
                        </div>

                        <div class="d-flex gap-3 mb-3">
                            <span class="badge-status status-diproses">Diproses</span>
                            <div class="text-muted small">
                                Pengaduan sedang ditinjau atau ditindaklanjuti.
                            </div>
                        </div>

                        <div class="d-flex gap-3 mb-3">
                            <span class="badge-status status-selesai">Selesai</span>
                            <div class="text-muted small">
                                Pengaduan telah selesai ditangani.
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <span class="badge-status status-ditolak">Ditolak</span>
                            <div class="text-muted small">
                                Pengaduan tidak dapat diproses.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
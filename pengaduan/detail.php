<?php
require_once __DIR__ . "/../config/database.php";
/** @var mysqli $conn */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/header.php";

$user_id = (int) $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/*
After Stored XSS Fix:
Data dari database ditampilkan menggunakan htmlspecialchars()
agar script tidak dijalankan oleh browser.
*/

$query = "SELECT * FROM pengaduan 
          WHERE id = ? 
          AND user_id = ? 
          LIMIT 1";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

$comment_error = "";
$comment_success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['komentar'])) {
    $komentar = trim($_POST['komentar']);

    if (!$data) {
        $comment_error = "Pengaduan tidak ditemukan.";
    } elseif ($komentar === "") {
        $comment_error = "Komentar tidak boleh kosong.";
    } elseif (strip_tags($komentar) !== $komentar) {
        $comment_error = "Komentar tidak boleh mengandung tag HTML atau Script.";
    } else {
        $query_komentar = "INSERT INTO komentar (pengaduan_id, user_id, komentar)
                           VALUES (?, ?, ?)";

        $stmt_komentar = mysqli_prepare($conn, $query_komentar);
        mysqli_stmt_bind_param($stmt_komentar, "iis", $id, $user_id, $komentar);

        if (mysqli_stmt_execute($stmt_komentar)) {
            $comment_success = "Komentar berhasil ditambahkan.";
        } else {
            $comment_error = "Komentar gagal ditambahkan.";
        }
    }
}

$query_list_komentar = "SELECT komentar.*, users.nama 
                        FROM komentar 
                        JOIN users ON komentar.user_id = users.id
                        WHERE komentar.pengaduan_id = ?
                        ORDER BY komentar.created_at DESC";

$stmt_list = mysqli_prepare($conn, $query_list_komentar);
mysqli_stmt_bind_param($stmt_list, "i", $id);
mysqli_stmt_execute($stmt_list);
$result_komentar = mysqli_stmt_get_result($stmt_list);

function badgeStatusDetail(string $status): string {
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
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="topbar-title">Detail Pengaduan</h1>
                <p class="topbar-subtitle">Informasi lengkap laporan pengaduan</p>
            </div>
        </header>

        <section class="page">
            <?php if (!$data): ?>
                <div class="clean-card p-5 text-center">
                    <div class="stat-icon icon-yellow mx-auto mb-3">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                    <h4 class="fw-bold">Pengaduan tidak ditemukan</h4>

                    <p class="text-muted">
                        Data tidak tersedia atau Anda tidak memiliki akses ke pengaduan ini.
                    </p>

                    <a href="daftar.php" class="btn btn-primary">
                        Kembali
                    </a>
                </div>
            <?php else: ?>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="clean-card p-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                                <div>
                                    <div class="text-muted small mb-2">Judul Pengaduan</div>

                                    <h2 class="page-title mb-2">
                                        <?= htmlspecialchars($data['judul']); ?>
                                    </h2>

                                    <p class="page-subtitle">
                                        Kode Pengaduan:
                                        <span class="fw-semibold">
                                            <?= htmlspecialchars($data['kode_pengaduan']); ?>
                                        </span>
                                    </p>
                                </div>

                                <div class="mt-1">
                                    <?= badgeStatusDetail($data['status']); ?>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-4">
                                <div class="text-muted small mb-2">Isi Pengaduan</div>

                                <div class="fs-6 lh-lg">
                                    <?= nl2br(htmlspecialchars($data['isi'])); ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="text-muted small mb-2">Lokasi</div>

                                <div class="fw-semibold">
                                    <i class="bi bi-geo-alt me-1 text-muted"></i>
                                    <?= htmlspecialchars($data['lokasi']); ?>
                                </div>
                            </div>

                            <div>
                                <div class="text-muted small mb-2">Bukti Pengaduan</div>

                                <?php if ($data['bukti_file']): ?>
                                    <a 
                                        href="../uploads/bukti/<?= htmlspecialchars($data['bukti_file']); ?>" 
                                        target="_blank" 
                                        class="btn btn-outline-primary"
                                    >
                                        <i class="bi bi-paperclip me-1"></i>
                                        Lihat Bukti
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Tidak ada file bukti.</span>
                                <?php endif; ?>
                            </div>

                            <hr class="my-4">

                            <div>
                                <h5 class="fw-bold mb-3">Komentar</h5>

                                <?php if ($comment_error): ?>
                                    <div class="alert alert-danger rounded-4">
                                        <?= htmlspecialchars($comment_error); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($comment_success): ?>
                                    <div class="alert alert-success rounded-4">
                                        <?= htmlspecialchars($comment_success); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="" class="mb-4" novalidate>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Tambah Komentar</label>
                                        <textarea 
                                            name="komentar" 
                                            rows="3" 
                                            class="form-control" 
                                            placeholder="Tulis komentar atau catatan tambahan..."
                                        ></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-chat-left-text me-1"></i>
                                        Kirim Komentar
                                    </button>
                                </form>

                                <?php if ($result_komentar && mysqli_num_rows($result_komentar) > 0): ?>
                                    <div class="comment-list">
                                        <?php while ($kom = mysqli_fetch_assoc($result_komentar)): ?>
                                            <div class="comment-item">
                                                <div class="d-flex align-items-start gap-3">
                                                    <div class="comment-avatar">
                                                        <?= strtoupper(substr($kom['nama'], 0, 1)); ?>
                                                    </div>

                                                    <div class="flex-grow-1">
                                                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-1">
                                                            <div class="fw-bold">
                                                                <?= htmlspecialchars($kom['nama']); ?>
                                                            </div>

                                                            <small class="text-muted">
                                                                <?= date("d M Y H:i", strtotime($kom['created_at'])); ?>
                                                            </small>
                                                        </div>

                                                        <div class="text-muted">
                                                            <?= nl2br(htmlspecialchars($kom['komentar'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted">
                                        Belum ada komentar pada pengaduan ini.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="clean-card p-4">
                            <h5 class="fw-bold mb-3">Informasi Laporan</h5>

                            <div class="mb-3">
                                <div class="text-muted small">Kategori</div>
                                <div class="fw-semibold">
                                    <?= htmlspecialchars($data['kategori']); ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="text-muted small">Status</div>
                                <div>
                                    <?= badgeStatusDetail($data['status']); ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="text-muted small">Tanggal</div>
                                <div class="fw-semibold">
                                    <?= date("d M Y H:i", strtotime($data['created_at'])); ?>
                                </div>
                            </div>

                            <hr>

                            <a href="daftar.php" class="btn btn-outline-secondary w-100 mb-2">
                                Kembali
                            </a>

                            <a 
                                href="hapus.php?id=<?= (int) $data['id']; ?>" 
                                class="btn btn-outline-danger w-100"
                                onclick="return confirm('Yakin ingin menghapus pengaduan ini?')"
                            >
                                Hapus Pengaduan
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
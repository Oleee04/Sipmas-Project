<?php
require_once __DIR__ . "/../config/database.php";
/** @var mysqli $conn */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/header.php";

$user_id = (int) $_SESSION['user_id'];

$keyword = isset($_GET['q']) ? trim($_GET['q']) : "";
$kategori_filter = isset($_GET['kategori']) ? trim($_GET['kategori']) : "";

$allowed_kategori = [
    "Infrastruktur",
    "Kebersihan",
    "Keamanan",
    "Pelayanan Publik",
    "Lingkungan",
    "Lainnya"
];

$kategori_query = in_array($kategori_filter, $allowed_kategori, true) ? $kategori_filter : "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
Query pencarian dan filter kategori menggunakan prepared statement.
Input dari user tidak digabung langsung ke query SQL.
*/

$search = "%" . $keyword . "%";

$query = "SELECT * FROM pengaduan 
          WHERE user_id = ?
          AND (? = '' OR kategori = ?)
          AND (
                ? = '' OR
                kode_pengaduan LIKE ? OR
                judul LIKE ? OR
                kategori LIKE ? OR
                lokasi LIKE ? OR
                status LIKE ?
          )
          ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "issssssss",
    $user_id,
    $kategori_query,
    $kategori_query,
    $keyword,
    $search,
    $search,
    $search,
    $search,
    $search
);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

function badgeStatus(string $status): string {
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
                <h1 class="topbar-title">Pengaduan Saya</h1>
                <p class="topbar-subtitle">Daftar laporan yang sudah Anda kirim</p>
            </div>
        </header>

        <section class="page">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                <div>
                    <h2 class="page-title mb-1">Daftar Pengaduan</h2>
                    <p class="page-subtitle">
                        Pantau status laporan dan cari pengaduan berdasarkan kata pencarian atau kategori.
                    </p>
                </div>

                <a href="tambah.php" class="btn btn-primary mt-3 mt-md-0">
                    <i class="bi bi-plus-circle me-1"></i>
                    Buat Pengaduan
                </a>
            </div>

            <div class="clean-card p-4 mb-4">
                <form method="GET" action="" id="filterPengaduanForm">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="bi bi-search text-muted"></i>
                                </span>

                                <input 
                                    type="text" 
                                    name="q" 
                                    class="form-control" 
                                    placeholder="Cari berdasarkan kode, judul, lokasi, atau status..."
                                    value="<?= htmlspecialchars($keyword); ?>"
                                >
                            </div>
                        </div>

                        <div class="col-md-4">
                            <select name="kategori" id="filterKategori" class="form-select">
                                <option value="">Semua kategori</option>
                                <option value="Infrastruktur" <?= $kategori_query === "Infrastruktur" ? "selected" : ""; ?>>Infrastruktur</option>
                                <option value="Kebersihan" <?= $kategori_query === "Kebersihan" ? "selected" : ""; ?>>Kebersihan</option>
                                <option value="Keamanan" <?= $kategori_query === "Keamanan" ? "selected" : ""; ?>>Keamanan</option>
                                <option value="Pelayanan Publik" <?= $kategori_query === "Pelayanan Publik" ? "selected" : ""; ?>>Pelayanan Publik</option>
                                <option value="Lingkungan" <?= $kategori_query === "Lingkungan" ? "selected" : ""; ?>>Lingkungan</option>
                                <option value="Lainnya" <?= $kategori_query === "Lainnya" ? "selected" : ""; ?>>Lainnya</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                Cari
                            </button>
                        </div>
                    </div>

                    <div class="mt-3 text-muted small">
                        Filter kategori dari URL:
                        <strong id="kategoriFilterPreview">Semua kategori</strong>
                    </div>

                    <?php if ($keyword !== "" || $kategori_filter !== ""): ?>
                        <div class="mt-2">
                            <span class="text-muted">
                                Filter aktif:

                                <?php if ($keyword !== ""): ?>
                                    pencarian <strong><?= htmlspecialchars($keyword); ?></strong>
                                <?php endif; ?>

                                <?php if ($keyword !== "" && $kategori_filter !== ""): ?>
                                    dan
                                <?php endif; ?>

                                <?php if ($kategori_filter !== ""): ?>
                                    kategori <strong><?= htmlspecialchars($kategori_filter); ?></strong>
                                <?php endif; ?>
                            </span>

                            <a href="daftar.php" class="ms-2 text-decoration-none fw-semibold">
                                Reset
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <div class="clean-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Data Pengaduan</h5>

                    <span class="text-muted small">
                        <?= $result ? mysqli_num_rows($result) : 0; ?> data ditemukan
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Lokasi</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
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
                                            <?= htmlspecialchars($row['lokasi']); ?>
                                        </td>

                                        <td>
                                            <?= badgeStatus($row['status']); ?>
                                        </td>

                                        <td>
                                            <?= date("d M Y", strtotime($row['created_at'])); ?>
                                        </td>

                                        <td class="text-end">
                                            <div class="action-buttons">
                                                <a href="detail.php?id=<?= (int) $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye me-1"></i>
                                                    Detail
                                                </a>

                                                <form 
                                                    method="POST" 
                                                    action="hapus.php" 
                                                    onsubmit="return confirm('Yakin ingin menghapus pengaduan ini?')"
                                                >
                                                    <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash me-1"></i>
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="stat-icon icon-blue mx-auto mb-3">
                                            <i class="bi bi-inbox"></i>
                                        </div>

                                        <?php if ($keyword !== "" || $kategori_filter !== ""): ?>
                                            <h5 class="fw-bold">Data tidak ditemukan</h5>

                                            <p class="text-muted mb-3">
                                                Tidak ada pengaduan yang cocok dengan filter yang digunakan.
                                            </p>

                                            <a href="daftar.php" class="btn btn-outline-primary">
                                                Tampilkan Semua
                                            </a>
                                        <?php else: ?>
                                            <h5 class="fw-bold">Belum ada pengaduan</h5>

                                            <p class="text-muted mb-3">
                                                Anda belum membuat pengaduan apa pun.
                                            </p>

                                            <a href="tambah.php" class="btn btn-primary">
                                                Buat Pengaduan
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
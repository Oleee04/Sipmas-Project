<?php
require_once __DIR__ . "/../config/database.php";
/** @var mysqli $conn */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/header.php";

$user_id = (int) $_SESSION['user_id'];
$error = "";

$judul = "";
$kategori = "";
$lokasi = "";
$isi = "";

function periksaFileDenganCommandServer(string $file_path): bool
{
    /*
    Command Injection Safe:
    Server memang menjalankan command untuk memeriksa file.
    Namun file yang diperiksa adalah file yang sudah disimpan dengan nama acak dari sistem,
    bukan nama file asli dari pengguna.
    */

    if (!function_exists('exec')) {
        return false;
    }

    $real_file = realpath($file_path);
    $upload_dir = realpath(__DIR__ . "/../uploads/bukti");

    if (!$real_file || !$upload_dir) {
        return false;
    }

    $upload_dir = rtrim($upload_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    /*
    Pastikan file yang diperiksa benar-benar berada di folder uploads/bukti.
    */
    if (strncmp($real_file, $upload_dir, strlen($upload_dir)) !== 0) {
        return false;
    }

    /*
    escapeshellarg membuat path file dianggap sebagai satu argumen,
    sehingga karakter seperti ;, &&, atau | tidak dibaca sebagai command tambahan.
    */
    $safe_file = escapeshellarg($real_file);

    if (PHP_OS_FAMILY === "Windows") {
        /*
        Laragon biasanya berjalan di Windows.
        certutil digunakan untuk menghitung hash file sebagai proses pemeriksaan server.
        */
        $command = "certutil -hashfile $safe_file SHA256";
    } else {
        /*
        Alternatif untuk Linux/Mac.
        */
        $command = "sha256sum $safe_file";
    }

    $output = [];
    $return_code = 0;

    exec($command, $output, $return_code);

    return $return_code === 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = isset($_POST['judul']) ? trim($_POST['judul']) : "";
    $kategori = isset($_POST['kategori']) ? trim($_POST['kategori']) : "";
    $lokasi = isset($_POST['lokasi']) ? trim($_POST['lokasi']) : "";
    $isi = isset($_POST['isi']) ? trim($_POST['isi']) : "";
    $bukti_file = "";

    if ($judul === "" || $kategori === "" || $lokasi === "" || $isi === "") {
        $error = "Semua field wajib diisi.";
    } elseif (strip_tags($judul) !== $judul || strip_tags($lokasi) !== $lokasi || strip_tags($isi) !== $isi) {
        $error = "Input tidak boleh mengandung tag HTML atau Script.";
    } else {
        /*
        After File Upload Fix:
        Upload bukti divalidasi menggunakan ekstensi, MIME type,
        ukuran file, dan pengecekan isi file.
        */

        if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] === 4) {
            $error = "File bukti wajib diunggah.";
        } elseif ($_FILES['bukti']['error'] !== 0) {
            $error = "Terjadi kesalahan saat mengunggah file bukti.";
        } else {
            $nama_file = $_FILES['bukti']['name'];
            $tmp_file = $_FILES['bukti']['tmp_name'];
            $ukuran_file = $_FILES['bukti']['size'];

            $max_size = 2 * 1024 * 1024;
            $ext = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

            $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];

            if (!in_array($ext, $allowed_ext, true)) {
                $error = "Format file tidak diperbolehkan. Gunakan JPG, JPEG, PNG, atau PDF.";
            } elseif ($ukuran_file > $max_size) {
                $error = "Ukuran file terlalu besar. Maksimal 2 MB.";
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $tmp_file);
                finfo_close($finfo);

                $valid_file = false;

                if ($ext === "jpg" || $ext === "jpeg") {
                    if ($mime_type === "image/jpeg" && getimagesize($tmp_file) !== false) {
                        $valid_file = true;
                    }
                } elseif ($ext === "png") {
                    if ($mime_type === "image/png" && getimagesize($tmp_file) !== false) {
                        $valid_file = true;
                    }
                } elseif ($ext === "pdf") {
                    $file_header = file_get_contents($tmp_file, false, null, 0, 4);

                    if ($mime_type === "application/pdf" && $file_header === "%PDF") {
                        $valid_file = true;
                    }
                }

                if (!$valid_file) {
                    $error = "File tidak valid. Pastikan file benar-benar berupa JPG, PNG, atau PDF.";
                } else {
                    $upload_dir = __DIR__ . "/../uploads/bukti/";

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    /*
                    Membuat .htaccess agar file script seperti PHP tidak bisa dieksekusi
                    di folder upload.
                    */
                    $htaccess_file = $upload_dir . ".htaccess";

                    if (!file_exists($htaccess_file)) {
                        $htaccess_content = "Options -Indexes\n";
                        $htaccess_content .= "<FilesMatch \"\\.(php|php3|php4|php5|phtml)$\">\n";
                        $htaccess_content .= "    Require all denied\n";
                        $htaccess_content .= "</FilesMatch>\n";

                        file_put_contents($htaccess_file, $htaccess_content);
                    }

                    /*
                    Nama file asli pengguna tidak dipakai.
                    File disimpan dengan nama acak agar aman dari manipulasi nama file.
                    */
                    $nama_acak = bin2hex(random_bytes(16));
                    $bukti_file = $nama_acak . "." . $ext;
                    $target = $upload_dir . $bukti_file;

                    if (!move_uploaded_file($tmp_file, $target)) {
                        $error = "File bukti gagal diunggah.";
                    } else {
                        /*
                        Proses tambahan di sisi server:
                        file diperiksa menggunakan command server.
                        Namun command memakai path file yang sudah aman,
                        bukan nama file asli dari pengguna.
                        */
                        $file_lolos_pemeriksaan = periksaFileDenganCommandServer($target);

                        if (!$file_lolos_pemeriksaan) {
                            if (file_exists($target)) {
                                unlink($target);
                            }

                            $bukti_file = "";
                            $error = "File bukti gagal diperiksa oleh sistem.";
                        }
                    }
                }
            }
        }

        if ($error === "") {
            $kode_pengaduan = "PGD-" . date("Ymd") . "-" . rand(1000, 9999);
            $status = "Dikirim";

            $query = "INSERT INTO pengaduan 
                      (user_id, kode_pengaduan, judul, kategori, lokasi, isi, bukti_file, status)
                      VALUES 
                      (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $query);

            mysqli_stmt_bind_param(
                $stmt,
                "isssssss",
                $user_id,
                $kode_pengaduan,
                $judul,
                $kategori,
                $lokasi,
                $isi,
                $bukti_file,
                $status
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: daftar.php");
                exit;
            } else {
                if ($bukti_file !== "") {
                    $file_tersimpan = __DIR__ . "/../uploads/bukti/" . $bukti_file;

                    if (file_exists($file_tersimpan)) {
                        unlink($file_tersimpan);
                    }
                }

                $error = "Pengaduan gagal dikirim.";
            }
        }
    }
}
?>

<div class="app-shell">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="topbar-title">Buat Pengaduan</h1>
                <p class="topbar-subtitle">Kirim laporan pengaduan baru</p>
            </div>
        </header>

        <section class="page">
            <div class="page-header">
                <h2 class="page-title">Form Pengaduan</h2>
                <p class="page-subtitle">
                    Lengkapi data pengaduan dengan jelas agar laporan mudah diproses.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="clean-card p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger rounded-4">
                                <?= htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data" novalidate>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Judul Pengaduan</label>
                                <input 
                                    type="text" 
                                    name="judul" 
                                    class="form-control" 
                                    placeholder="Contoh: Lampu Jalan Mati di Sukatani"
                                    value="<?= htmlspecialchars($judul); ?>"
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Kategori</label>
                                <select name="kategori" class="form-select">
                                    <option value="">Pilih kategori</option>
                                    <option value="Infrastruktur" <?= $kategori === "Infrastruktur" ? "selected" : ""; ?>>Infrastruktur</option>
                                    <option value="Kebersihan" <?= $kategori === "Kebersihan" ? "selected" : ""; ?>>Kebersihan</option>
                                    <option value="Keamanan" <?= $kategori === "Keamanan" ? "selected" : ""; ?>>Keamanan</option>
                                    <option value="Pelayanan Publik" <?= $kategori === "Pelayanan Publik" ? "selected" : ""; ?>>Pelayanan Publik</option>
                                    <option value="Lingkungan" <?= $kategori === "Lingkungan" ? "selected" : ""; ?>>Lingkungan</option>
                                    <option value="Lainnya" <?= $kategori === "Lainnya" ? "selected" : ""; ?>>Lainnya</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Lokasi Kejadian</label>
                                <input 
                                    type="text" 
                                    name="lokasi" 
                                    class="form-control" 
                                    placeholder="Contoh: Jl. Raya Sukatani, dekat area permukiman warga"
                                    value="<?= htmlspecialchars($lokasi); ?>"
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Isi Pengaduan</label>
                                <textarea 
                                    name="isi" 
                                    rows="5" 
                                    class="form-control" 
                                    placeholder="Tuliskan kronologi atau detail pengaduan..."
                                ><?= htmlspecialchars($isi); ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Upload Bukti</label>
                                <input 
                                    type="file" 
                                    name="bukti" 
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                >
                                <small class="text-muted">
                                    File bukti wajib diunggah. Format: JPG, JPEG, PNG, atau PDF. Maksimal 2 MB.
                                </small>
                                <?php if ($error): ?>
                                    <div class="text-muted small mt-1">
                                        Catatan: file perlu dipilih ulang setelah validasi gagal.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="daftar.php" class="btn btn-outline-secondary">
                                    Batal
                                </a>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-1"></i>
                                    Kirim Pengaduan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="clean-card p-4">
                        <h5 class="fw-bold mb-3">Tips Pengaduan</h5>

                        <div class="d-flex gap-3 mb-3">
                            <div class="stat-icon icon-blue">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Judul jelas</div>
                                <div class="text-muted small">
                                    Gunakan judul singkat sesuai masalah.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 mb-3">
                            <div class="stat-icon icon-yellow">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Lokasi lengkap</div>
                                <div class="text-muted small">
                                    Tuliskan lokasi kejadian sejelas mungkin.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <div class="stat-icon icon-green">
                                <i class="bi bi-paperclip"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Bukti wajib</div>
                                <div class="text-muted small">
                                    Upload bukti berupa gambar asli atau PDF valid.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clean-card p-4 mt-3">
                        <h5 class="fw-bold mb-2">Status Awal</h5>
                        <p class="text-muted mb-0">
                            Setelah dikirim, pengaduan akan masuk dengan status
                            <span class="badge-status status-dikirim">Dikirim</span>.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

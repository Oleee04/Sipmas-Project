<?php
require_once __DIR__ . "/../config/database.php";
/** @var mysqli $conn */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/header.php";

$user_id = (int) $_SESSION['user_id'];
$error = "";
$success = "";

$query_user = "SELECT id, nama, username, email, role, created_at 
               FROM users 
               WHERE id = ? 
               LIMIT 1";

$stmt_user = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user = mysqli_fetch_assoc($result_user);

if (!$user) {
    header("Location: ../logout.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : "";
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password_baru = isset($_POST['password_baru']) ? $_POST['password_baru'] : "";

    if ($nama === "" || $email === "") {
        $error = "Nama dan email wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {
        if ($password_baru !== "") {
            /*
            After Cryptography Fix:
            Password baru disimpan menggunakan password_hash(), bukan MD5.
            */
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);

            $query_update = "UPDATE users 
                             SET nama = ?,
                                 email = ?,
                                 password = ?
                             WHERE id = ?";

            $stmt_update = mysqli_prepare($conn, $query_update);
            mysqli_stmt_bind_param($stmt_update, "sssi", $nama, $email, $password_hash, $user_id);
        } else {
            $query_update = "UPDATE users 
                             SET nama = ?,
                                 email = ?
                             WHERE id = ?";

            $stmt_update = mysqli_prepare($conn, $query_update);
            mysqli_stmt_bind_param($stmt_update, "ssi", $nama, $email, $user_id);
        }

        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['nama'] = $nama;
            $success = "Profil berhasil diperbarui.";

            $query_user = "SELECT id, nama, username, email, role, created_at 
                           FROM users 
                           WHERE id = ? 
                           LIMIT 1";

            $stmt_user = mysqli_prepare($conn, $query_user);
            mysqli_stmt_bind_param($stmt_user, "i", $user_id);
            mysqli_stmt_execute($stmt_user);
            $result_user = mysqli_stmt_get_result($stmt_user);
            $user = mysqli_fetch_assoc($result_user);
        } else {
            $error = "Profil gagal diperbarui.";
        }
    }
}
?>

<div class="app-shell">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="topbar-title">Profil</h1>
                <p class="topbar-subtitle">Kelola informasi akun Anda</p>
            </div>
        </header>

        <section class="page">
            <div class="page-header">
                <h2 class="page-title">Edit Profil</h2>
                <p class="page-subtitle">
                    Perbarui data akun yang digunakan untuk mengakses SIPMAS.
                </p>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="clean-card p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger rounded-4">
                                <?= htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success rounded-4">
                                <?= htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="formProfil" novalidate>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Lengkap</label>
                                <input 
                                    type="text" 
                                    name="nama" 
                                    class="form-control" 
                                    value="<?= htmlspecialchars($user['nama']); ?>"
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Username</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    value="<?= htmlspecialchars($user['username']); ?>"
                                    disabled
                                >
                                <small class="text-muted">
                                    Username tidak dapat diubah.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email</label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    class="form-control" 
                                    value="<?= htmlspecialchars($user['email']); ?>"
                                >
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Password Baru</label>
                                <input 
                                    type="password" 
                                    name="password_baru" 
                                    class="form-control" 
                                    placeholder="Kosongkan jika tidak ingin mengganti password"
                                >
                                <small class="text-muted">
                                    Jika diisi, password lama akan diganti.
                                </small>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="../dashboard.php" class="btn btn-outline-secondary">
                                    Batal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="clean-card p-4">
                        <div class="text-center">
                            <div class="user-avatar mx-auto mb-3" style="width:72px;height:72px;font-size:28px;">
                                <?= htmlspecialchars(strtoupper(substr($user['nama'], 0, 1))); ?>
                            </div>

                            <h5 class="fw-bold mb-1">
                                <?= htmlspecialchars($user['nama']); ?>
                            </h5>
                            <p class="text-muted mb-4">
                                <?= htmlspecialchars($user['email']); ?>
                            </p>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <div class="text-muted small">Username</div>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($user['username']); ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Role</div>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($user['role']); ?>
                            </div>
                        </div>

                        <div>
                            <div class="text-muted small">Tanggal Daftar</div>
                            <div class="fw-semibold">
                                <?= htmlspecialchars(date("d M Y", strtotime($user['created_at']))); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
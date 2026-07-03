<?php
require_once __DIR__ . "/config/database.php";
/** @var mysqli $conn */

require_once __DIR__ . "/config/session.php";
require_once __DIR__ . "/includes/header.php";

$error = "";
$success = "";

$nama = "";
$username = "";
$email = "";

function generateCaptchaCode(int $length = 5): string
{
    $characters = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    $code = "";

    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $code;
}

if (empty($_SESSION['captcha_code'])) {
    $_SESSION['captcha_code'] = generateCaptchaCode();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : "";
    $username = isset($_POST['username']) ? trim($_POST['username']) : "";
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password = isset($_POST['password']) ? $_POST['password'] : "";
    $konfirmasi = isset($_POST['konfirmasi']) ? $_POST['konfirmasi'] : "";
    $captcha_input = isset($_POST['captcha_input']) ? trim($_POST['captcha_input']) : "";

    $captcha_session = isset($_SESSION['captcha_code']) ? $_SESSION['captcha_code'] : "";

    if ($nama === "" || $username === "" || $email === "" || $password === "" || $konfirmasi === "") {
        $error = "Semua field wajib diisi.";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak sesuai.";
    } elseif ($captcha_input === "") {
        $error = "CAPTCHA wajib diisi.";
    } elseif (
        $captcha_session === "" ||
        !hash_equals(strtoupper($captcha_session), strtoupper($captcha_input))
    ) {
        $error = "Jawaban CAPTCHA salah.";
    } else {
        /*
        After Cryptography Fix:
        Password disimpan menggunakan password_hash(), bukan MD5.
        */
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $query_cek = "SELECT id FROM users 
                      WHERE username = ? 
                      OR email = ? 
                      LIMIT 1";

        $stmt_cek = mysqli_prepare($conn, $query_cek);
        mysqli_stmt_bind_param($stmt_cek, "ss", $username, $email);
        mysqli_stmt_execute($stmt_cek);
        mysqli_stmt_store_result($stmt_cek);

        if (mysqli_stmt_num_rows($stmt_cek) > 0) {
            $error = "Username atau email sudah digunakan.";
        } else {
            $query = "INSERT INTO users 
                      (nama, username, email, password) 
                      VALUES (?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssss", $nama, $username, $email, $password_hash);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Registrasi berhasil. Silakan login.";

                $nama = "";
                $username = "";
                $email = "";
            } else {
                $error = "Registrasi gagal.";
            }
        }
    }

    $_SESSION['captcha_code'] = generateCaptchaCode();
}

$captcha_display = $_SESSION['captcha_code'];
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="bi bi-person-plus-fill"></i>
        </div>

        <div class="text-center mb-4">
            <h3 class="fw-bold mb-1">Buat Akun</h3>
            <p class="text-muted mb-0">
                Daftar untuk mengirim pengaduan masyarakat.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger rounded-4">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success rounded-4">
                <?= htmlspecialchars($success); ?>
                <div class="mt-2">
                    <a href="login.php" class="fw-semibold text-success">Login sekarang</a>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Lengkap</label>
                <input 
                    type="text" 
                    name="nama" 
                    class="form-control" 
                    placeholder="Masukkan nama lengkap"
                    value="<?= htmlspecialchars($nama); ?>"
                >
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    class="form-control" 
                    placeholder="Masukkan username"
                    value="<?= htmlspecialchars($username); ?>"
                >
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input 
                    type="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="nama@email.com"
                    value="<?= htmlspecialchars($email); ?>"
                >
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Masukkan password"
                >
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Konfirmasi Password</label>
                <input 
                    type="password" 
                    name="konfirmasi" 
                    class="form-control" 
                    placeholder="Ulangi password"
                >
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">CAPTCHA</label>

                <div class="p-3 rounded-4 bg-light border mb-2 text-center">
                    <span class="text-muted d-block mb-2">
                        Masukkan kode berikut
                    </span>

                    <span class="fw-bold fs-4" style="letter-spacing: 5px;">
                        <?= htmlspecialchars($captcha_display); ?>
                    </span>
                </div>

                <input 
                    type="text" 
                    name="captcha_input" 
                    class="form-control" 
                    placeholder="Masukkan kode CAPTCHA"
                >
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="bi bi-person-plus me-1"></i>
                Daftar
            </button>

            <p class="text-center text-muted mb-0">
                Sudah punya akun?
                <a href="login.php" class="text-decoration-none fw-semibold">Login</a>
            </p>

            <div class="text-center mt-3">
                <a href="index.php" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i>
                    Kembali ke halaman utama
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
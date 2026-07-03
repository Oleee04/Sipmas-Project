<?php
require_once __DIR__ . "/config/database.php";
/** @var mysqli $conn */

require_once __DIR__ . "/includes/header.php";

$error = "";

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : "";
    $password = isset($_POST['password']) ? $_POST['password'] : "";
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if ($username === "" || $password === "") {
        $error = "Username dan password wajib diisi.";
    } else {
        $max_attempts = 3;
        $lock_minutes = 5;

        $query_attempt = "SELECT COUNT(*) AS total 
                          FROM login_attempts
                          WHERE username = ?
                          AND ip_address = ?
                          AND status = 'gagal'
                          AND created_at >= (NOW() - INTERVAL ? MINUTE)";

        $stmt_attempt = mysqli_prepare($conn, $query_attempt);
        mysqli_stmt_bind_param($stmt_attempt, "ssi", $username, $ip_address, $lock_minutes);
        mysqli_stmt_execute($stmt_attempt);
        $result_attempt = mysqli_stmt_get_result($stmt_attempt);
        $attempt_data = mysqli_fetch_assoc($result_attempt);

        $total_failed = isset($attempt_data['total']) ? (int) $attempt_data['total'] : 0;

        if ($total_failed >= $max_attempts) {
            $error = "Terlalu banyak percobaan login gagal. Silakan coba lagi beberapa menit kemudian.";
        } else {
            /*
            After Cryptography Fix:
            User dicari berdasarkan username.
            Password dicek menggunakan password_verify().
            */
            $query_user = "SELECT id, nama, username, password, role 
                           FROM users 
                           WHERE username = ? 
                           LIMIT 1";

            $stmt_user = mysqli_prepare($conn, $query_user);
            mysqli_stmt_bind_param($stmt_user, "s", $username);
            mysqli_stmt_execute($stmt_user);
            $result_user = mysqli_stmt_get_result($stmt_user);

            $login_berhasil = false;
            $user = null;

            if ($result_user && mysqli_num_rows($result_user) === 1) {
                $user = mysqli_fetch_assoc($result_user);

                if (password_verify($password, $user['password'])) {
                    $login_berhasil = true;
                }
            }

            if ($login_berhasil && $user !== null) {
                $query_success = "INSERT INTO login_attempts 
                                  (username, ip_address, status)
                                  VALUES (?, ?, 'berhasil')";

                $stmt_success = mysqli_prepare($conn, $query_success);
                mysqli_stmt_bind_param($stmt_success, "ss", $username, $ip_address);
                mysqli_stmt_execute($stmt_success);

                $query_clear = "DELETE FROM login_attempts 
                                WHERE username = ? 
                                AND ip_address = ? 
                                AND status = 'gagal'";

                $stmt_clear = mysqli_prepare($conn, $query_clear);
                mysqli_stmt_bind_param($stmt_clear, "ss", $username, $ip_address);
                mysqli_stmt_execute($stmt_clear);

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                header("Location: dashboard.php");
                exit;
            } else {
                $query_failed = "INSERT INTO login_attempts 
                                 (username, ip_address, status)
                                 VALUES (?, ?, 'gagal')";

                $stmt_failed = mysqli_prepare($conn, $query_failed);
                mysqli_stmt_bind_param($stmt_failed, "ss", $username, $ip_address);
                mysqli_stmt_execute($stmt_failed);

                sleep(1);

                $sisa_percobaan = $max_attempts - ($total_failed + 1);

                if ($sisa_percobaan <= 0) {
                    $error = "Terlalu banyak percobaan login gagal. Silakan coba lagi beberapa menit kemudian.";
                } else {
                    $error = "Username atau password salah. Sisa percobaan: " . $sisa_percobaan . ".";
                }
            }
        }
    }
}
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="bi bi-megaphone-fill"></i>
        </div>

        <div class="text-center mb-4">
            <h3 class="fw-bold mb-1">Login SIPMAS</h3>
            <p class="text-muted mb-0">
                Masuk untuk mengelola pengaduan Anda
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger rounded-4">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    class="form-control" 
                    placeholder="Masukkan username"
                    value="<?= isset($username) ? htmlspecialchars($username) : ""; ?>"
                >
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Masukkan password"
                >
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i>
                Login
            </button>
        </form>

        <div class="text-center mt-4">
            <span class="text-muted">Belum punya akun?</span>
            <a href="register.php" class="text-decoration-none fw-semibold">
                Daftar
            </a>
        </div>

        <div class="text-center mt-2">
            <a href="index.php" class="text-decoration-none text-muted small">
                Kembali ke beranda
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
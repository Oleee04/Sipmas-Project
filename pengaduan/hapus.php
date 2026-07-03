<?php
require_once __DIR__ . "/../config/database.php";
/** @var mysqli $conn */

require_once __DIR__ . "/../includes/auth.php";

$user_id = (int) $_SESSION['user_id'];

/*
After CSRF Fix:
Aksi hapus hanya diproses melalui metode POST dan harus membawa CSRF token yang valid.
Jika file ini dibuka langsung melalui URL seperti hapus.php?id=3, data tidak akan terhapus.
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: daftar.php");
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : "";

if (
    empty($_SESSION['csrf_token']) ||
    empty($csrf_token) ||
    !hash_equals($_SESSION['csrf_token'], $csrf_token)
) {
    header("Location: daftar.php");
    exit;
}

if ($id > 0) {
    $query = "DELETE FROM pengaduan 
              WHERE id = ? 
              AND user_id = ?";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    mysqli_stmt_execute($stmt);
}

header("Location: daftar.php");
exit;
?>
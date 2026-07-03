<?php
require_once __DIR__ . "/includes/header.php";
?>

<div class="landing-page">
    <nav class="landing-nav">
        <a href="/sipmas/index.php" class="landing-brand">
            <div class="landing-logo">
                <i class="bi bi-megaphone-fill"></i>
            </div>
            <div>
                <div class="landing-brand-name">SIPMAS</div>
                <div class="landing-brand-desc">Pengaduan Masyarakat</div>
            </div>
        </a>

        <div class="landing-actions">
            <a href="login.php" class="btn btn-outline-primary">
                Login
            </a>
            <a href="register.php" class="btn btn-primary">
                Daftar
            </a>
        </div>
    </nav>

    <main class="landing-main">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <div class="landing-badge">
                        <i class="bi bi-shield-check"></i>
                        Sistem Informasi Pengaduan Masyarakat
                    </div>

                    <h1 class="landing-title">
                        Sampaikan pengaduan masyarakat dengan mudah dan terdata.
                    </h1>

                    <p class="landing-subtitle">
                        SIPMAS membantu masyarakat membuat laporan, mengunggah bukti,
                        memantau status, dan mengelola pengaduan secara lebih rapi.
                    </p>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="login.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-1"></i>
                            Login
                        </a>

                        <a href="register.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-person-plus me-1"></i>
                            Daftar
                        </a>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="landing-card">
                        <h4 class="fw-bold mb-4">Fitur Utama SIPMAS</h4>

                        <div class="landing-feature">
                            <div class="landing-feature-icon icon-blue">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Buat Pengaduan</div>
                                <p class="text-muted mb-0">
                                    Kirim laporan berdasarkan kategori, lokasi, dan deskripsi.
                                </p>
                            </div>
                        </div>

                        <div class="landing-feature">
                            <div class="landing-feature-icon icon-yellow">
                                <i class="bi bi-paperclip"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Upload Bukti</div>
                                <p class="text-muted mb-0">
                                    Lampirkan foto atau dokumen pendukung pengaduan.
                                </p>
                            </div>
                        </div>

                        <div class="landing-feature">
                            <div class="landing-feature-icon icon-green">
                                <i class="bi bi-list-check"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Pantau Pengaduan</div>
                                <p class="text-muted mb-0">
                                    Lihat daftar laporan, detail pengaduan, status, dan komentar.
                                </p>
                            </div>
                        </div>

                        <div class="landing-feature mb-0">
                            <div class="landing-feature-icon icon-blue">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Kelola Profil</div>
                                <p class="text-muted mb-0">
                                    Perbarui data akun dan informasi pengguna.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
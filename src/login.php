<?php

declare(strict_types=1);

require_once __DIR__ . '/koneksi.php';

use App\Core\Auth;

if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } elseif (Auth::attempt($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}

$csrfToken = Auth::csrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - App-Topsis</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/vendors/simplebar/css/simplebar.css">
    <?php include 'layout/head.php'; ?>
</head>
<body class="bg-body-tertiary min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card-group d-block d-md-flex row shadow-sm">
                    <div class="card col-md-7 p-4 mb-0">
                        <div class="card-body">
                            <h2 class="fw-bold mb-1">Masuk</h2>
                            <p class="text-body-secondary mb-4">Sistem Penunjang Keputusan TOPSIS</p>

                            <?php if ($error !== ''): ?>
                                <div class="alert alert-danger py-2 small" role="alert">
                                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>

                            <form action="login.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="mb-3">
                                    <label for="username" class="form-label small fw-semibold">Username</label>
                                    <input class="form-control" type="text" id="username" name="username" required autofocus>
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label small fw-semibold">Password</label>
                                    <input class="form-control" type="password" id="password" name="password" required>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="submit" name="submit" class="btn btn-dark px-4">Masuk</button>
                                    <a href="register.php" class="text-decoration-none small text-body-secondary">Daftar Akun Baru</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card col-md-5 text-white bg-dark py-5">
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <h3 class="fw-bold mb-3">App-Topsis</h3>
                            <p class="small text-white-50 mb-4">Perhitungan metode TOPSIS cepat, akurat, dan berbasis data matriks keputusan.</p>
                            <div>
                                <a href="register.php" class="btn btn-outline-light btn-sm px-4">Registrasi</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

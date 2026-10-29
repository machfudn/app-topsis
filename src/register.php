<?php

declare(strict_types=1);

require_once __DIR__ . '/koneksi.php';

use App\Core\Auth;
use App\Core\Database;

if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $name     = trim((string)($_POST['name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $repass   = (string)($_POST['repassword'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            $error = 'Semua field wajib diisi.';
        } elseif ($password !== $repass) {
            $error = 'Konfirmasi password tidak cocok.';
        } elseif (strlen($password) < 3) {
            $error = 'Password minimal 3 karakter.';
        } else {
            $db = Database::getConnection();
            $check = $db->prepare('SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1');
            $check->execute([':u' => $username, ':e' => $email]);

            if ($check->fetch()) {
                $error = 'Username atau Email sudah terdaftar.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('INSERT INTO users (name, username, email, password) VALUES (:name, :u, :e, :p)');
                $stmt->execute([
                    ':name' => $name ?: $username,
                    ':u'    => $username,
                    ':e'    => $email,
                    ':p'    => $hash,
                ]);

                // Auto login after registration
                Auth::attempt($username, $password);
                header('Location: index.php');
                exit;
            }
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
    <title>Registrasi - App-Topsis</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/vendors/simplebar/css/simplebar.css">
    <?php include 'layout/head.php'; ?>
</head>
<body class="bg-body-tertiary min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm p-3">
                    <div class="card-body">
                        <h2 class="fw-bold mb-1">Daftar Akun</h2>
                        <p class="text-body-secondary small mb-4">Buat akun untuk mengelola SPK TOPSIS</p>

                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger py-2 small" role="alert">
                                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <form action="register.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="mb-3">
                                <label for="name" class="form-label small fw-semibold">Nama Lengkap</label>
                                <input class="form-control" type="text" id="name" name="name" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label small fw-semibold">Username</label>
                                <input class="form-control" type="text" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label small fw-semibold">Email</label>
                                <input class="form-control" type="email" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label small fw-semibold">Password</label>
                                <input class="form-control" type="password" id="password" name="password" required>
                            </div>
                            <div class="mb-4">
                                <label for="repassword" class="form-label small fw-semibold">Ulangi Password</label>
                                <input class="form-control" type="password" id="repassword" name="repassword" required>
                            </div>
                            <button type="submit" name="submit" class="btn btn-dark w-100 mb-3">Daftar Sekarang</button>
                            <div class="text-center">
                                <a href="login.php" class="text-decoration-none small text-body-secondary">Sudah punya akun? Masuk</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

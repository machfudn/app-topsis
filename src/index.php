<?php

declare(strict_types=1);

require_once __DIR__ . '/koneksi.php';

use App\Core\Auth;
use App\Core\Database;

Auth::requireAuth();
$user = Auth::user();

$db = Database::getConnection();
$kriteriaCount = (int)$db->query('SELECT COUNT(*) FROM kriteria')->fetchColumn();
$alternatifCount = (int)$db->query('SELECT COUNT(*) FROM alternatif')->fetchColumn();
$matriksCount = (int)$db->query('SELECT COUNT(*) FROM nilai_matriks')->fetchColumn();

$expectedCells = $kriteriaCount * $alternatifCount;
$isReady = $kriteriaCount > 0 && $alternatifCount > 0 && $matriksCount >= $expectedCells;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - App-Topsis</title>
    <?php include 'layout/head.php'; ?>
</head>
<body>
    <?php include 'layout/sidebar.php'; ?>
    <div class="wrapper d-flex flex-column min-vh-100">
        <?php include 'layout/header.php'; ?>
        <div class="body flex-grow-1 px-3">
            <div class="container-lg">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-1">Dashboard</h3>
                        <p class="text-body-secondary small mb-0">Selamat datang kembali, <?= htmlspecialchars($user['name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <a href="nilaipreferensi.php" class="btn btn-dark btn-sm <?= $isReady ? '' : 'disabled' ?>">
                            Hitung TOPSIS
                        </a>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="text-body-secondary small text-uppercase fw-semibold">Kriteria</div>
                                <div class="fs-4 fw-bold my-1"><?= $kriteriaCount ?></div>
                                <div class="small text-body-secondary">
                                    <a href="kriteria.php" class="text-decoration-none">Kelola Kriteria &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="text-body-secondary small text-uppercase fw-semibold">Alternatif</div>
                                <div class="fs-4 fw-bold my-1"><?= $alternatifCount ?></div>
                                <div class="small text-body-secondary">
                                    <a href="alternatif.php" class="text-decoration-none">Kelola Alternatif &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="text-body-secondary small text-uppercase fw-semibold">Data Matriks</div>
                                <div class="fs-4 fw-bold my-1"><?= $matriksCount ?><span class="fs-6 fw-normal text-muted">/<?= $expectedCells ?></span></div>
                                <div class="small text-body-secondary">
                                    <a href="nilaimatriks.php" class="text-decoration-none">Lihat Matriks &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="text-body-secondary small text-uppercase fw-semibold">Status SPK</div>
                                <div class="fs-5 fw-bold my-1">
                                    <?php if ($isReady): ?>
                                        <span class="badge bg-dark">Siap Dihitung</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Data Belum Lengkap</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small text-body-secondary">
                                    <a href="import.php" class="text-decoration-none">Import Data CSV &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="fw-bold mb-0">Menu Cepat</h5>
                    </div>
                    <div class="card-body">
                        <div class="row row-cols-1 row-cols-md-3 g-3">
                            <div class="col">
                                <a href="kriteria.php" class="text-decoration-none text-reset">
                                    <div class="p-3 border rounded hover-shadow bg-body">
                                        <div class="fw-semibold">1. Data Kriteria</div>
                                        <div class="small text-body-secondary">Input dan tentukan bobot serta jenis kriteria (benefit/cost).</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col">
                                <a href="alternatif.php" class="text-decoration-none text-reset">
                                    <div class="p-3 border rounded hover-shadow bg-body">
                                        <div class="fw-semibold">2. Data Alternatif</div>
                                        <div class="small text-body-secondary">Kelola daftar pilihan atau alternatif yang akan dianalisis.</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col">
                                <a href="inputnilaimatriks.php" class="text-decoration-none text-reset">
                                    <div class="p-3 border rounded hover-shadow bg-body">
                                        <div class="fw-semibold">3. Input Matriks Nilai</div>
                                        <div class="small text-body-secondary">Isi nilai kecocokan setiap alternatif terhadap masing-masing kriteria.</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col">
                                <a href="nilaimatriks.php" class="text-decoration-none text-reset">
                                    <div class="p-3 border rounded hover-shadow bg-body">
                                        <div class="fw-semibold">4. Tabulasi Matriks</div>
                                        <div class="small text-body-secondary">Tinjau matriks keputusan secara menyeluruh dalam tabel.</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col">
                                <a href="nilaipreferensi.php" class="text-decoration-none text-reset">
                                    <div class="p-3 border rounded hover-shadow bg-body">
                                        <div class="fw-semibold">5. Perhitungan & Ranking</div>
                                        <div class="small text-body-secondary">Hasil normalisasi, bobot, solusi ideal, hingga ranking prioritas.</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col">
                                <a href="import.php" class="text-decoration-none text-reset">
                                    <div class="p-3 border rounded hover-shadow bg-body">
                                        <div class="fw-semibold">6. Import File CSV</div>
                                        <div class="small text-body-secondary">Impor data kriteria, alternatif, atau matriks via file CSV cepat.</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php include 'layout/footer.php'; ?>
    </div>
    <script src="assets/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <script src="assets/vendors/simplebar/js/simplebar.min.js"></script>
</body>
</html>

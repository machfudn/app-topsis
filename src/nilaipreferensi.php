<?php

declare(strict_types=1);

require_once __DIR__ . '/koneksi.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\TopsisEngine;

Auth::requireAuth();
$db = Database::getConnection();

// Detect table & column names for backward compatibility
$tMatriks = (bool)$db->query("SHOW TABLES LIKE 'nilai_matriks'")->fetch() ? 'nilai_matriks' : 'nilai_matrik';
$hasNewKritCol = (bool)$db->query("SHOW COLUMNS FROM kriteria LIKE 'id'")->fetch();
$cId   = $hasNewKritCol ? 'id' : 'id_kriteria';
$cName = $hasNewKritCol ? 'nama' : 'nama_kriteria';

$hasNewAltCol = (bool)$db->query("SHOW COLUMNS FROM alternatif LIKE 'id'")->fetch();
$aId   = $hasNewAltCol ? 'id' : 'id_alternatif';
$aName = $hasNewAltCol ? 'nama' : 'nm_alternatif';

// Fetch criteria
$critRows = $db->query("SELECT {$cId} AS id, {$cName} AS nama, bobot, jenis FROM kriteria ORDER BY CAST(SUBSTRING({$cId} FROM 2) AS UNSIGNED) ASC, {$cId} ASC")->fetchAll();
$criteria = [];
foreach ($critRows as $row) {
    $criteria[$row['id']] = $row;
}

// Fetch alternatives
$altRows = $db->query("SELECT {$aId} AS id, {$aName} AS nama FROM alternatif ORDER BY CAST(SUBSTRING({$aId} FROM 2) AS UNSIGNED) ASC, {$aId} ASC")->fetchAll();
$alternatives = [];
foreach ($altRows as $row) {
    $alternatives[$row['id']] = $row['nama'];
}

// Fetch matrix values in one query
$stmt = $db->query("SELECT id_alternatif, id_kriteria, nilai FROM {$tMatriks}");
$matrixRows = $stmt->fetchAll();

$matrix = [];
foreach ($matrixRows as $row) {
    $matrix[$row['id_alternatif']][$row['id_kriteria']] = (float)$row['nilai'];
}

// Execute TOPSIS in-memory calculation
$result = TopsisEngine::calculate($criteria, $alternatives, $matrix);

$activeTab = trim((string)($_GET['tab'] ?? 'ranking'));
$validTabs = ['ranking', 'normalisasi', 'terbobot', 'ideal', 'jarak'];
if (!in_array($activeTab, $validTabs, true)) {
    $activeTab = 'ranking';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Perhitungan TOPSIS - App-Topsis</title>
    <?php include 'layout/head.php'; ?>
</head>
<body>
    <?php include 'layout/sidebar.php'; ?>
    <div class="wrapper d-flex flex-column min-vh-100">
        <?php include 'layout/header.php'; ?>
        <div class="body flex-grow-1 px-3">
            <div class="container-lg">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="fw-bold mb-0">Hasil Perhitungan & Ranking TOPSIS</h4>
                        <span class="small text-body-secondary">Kalkulasi in-memory real-time metode TOPSIS</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button onclick="window.print()" class="btn btn-outline-dark btn-sm">Cetak Laporan</button>
                        <a href="nilaimatriks.php" class="btn btn-outline-dark btn-sm">Matriks Asal</a>
                    </div>
                </div>

                <?php if (empty($criteria) || empty($alternatives)): ?>
                    <div class="alert alert-warning small">
                        Data kriteria atau alternatif masih kosong. Silakan lengkapi data terlebih dahulu.
                    </div>
                <?php else: ?>
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'ranking' ? 'active fw-bold' : '' ?>" href="nilaipreferensi.php?tab=ranking">
                                1. Ranking Akhir (V<sub>i</sub>)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'normalisasi' ? 'active fw-bold' : '' ?>" href="nilaipreferensi.php?tab=normalisasi">
                                2. Matriks Normalisasi (R)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'terbobot' ? 'active fw-bold' : '' ?>" href="nilaipreferensi.php?tab=terbobot">
                                3. Matriks Terbobot (Y)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'ideal' ? 'active fw-bold' : '' ?>" href="nilaipreferensi.php?tab=ideal">
                                4. Solusi Ideal (A<sup>+</sup> / A<sup>-</sup>)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'jarak' ? 'active fw-bold' : '' ?>" href="nilaipreferensi.php?tab=jarak">
                                5. Jarak Separasi (D<sup>+</sup> / D<sup>-</sup>)
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- TAB 1: RANKING AKHIR -->
                        <?php if ($activeTab === 'ranking'): ?>
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-transparent py-3">
                                    <strong>Peringkat Nilai Preferensi (V<sub>i</sub>)</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 80px;" class="text-center">Rank</th>
                                                <th style="width: 100px;">Kode</th>
                                                <th>Nama Alternatif</th>
                                                <th style="width: 130px;" class="text-end">D<sup>+</sup></th>
                                                <th style="width: 130px;" class="text-end">D<sup>-</sup></th>
                                                <th style="width: 140px;" class="text-end">Nilai V<sub>i</sub></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($result['rankings'] as $row): ?>
                                                <tr class="<?= $row['rank'] === 1 ? 'table-dark' : '' ?>">
                                                    <td class="text-center fw-bold">
                                                        <?php if ($row['rank'] === 1): ?>
                                                            <span class="badge bg-light text-dark rounded-pill">1</span>
                                                        <?php else: ?>
                                                            <?= $row['rank'] ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="font-monospace fw-bold"><?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="text-end font-monospace"><?= number_format((float)$row['dPlus'], 4) ?></td>
                                                    <td class="text-end font-monospace"><?= number_format((float)$row['dMinus'], 4) ?></td>
                                                    <td class="text-end font-monospace fw-bold fs-6"><?= number_format((float)$row['score'], 4) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        <!-- TAB 2: MATRIKS NORMALISASI -->
                        <?php elseif ($activeTab === 'normalisasi'): ?>
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-transparent py-3">
                                    <strong>Matriks Ternormalisasi (R)</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 80px;">Kode</th>
                                                <th>Nama Alternatif</th>
                                                <?php foreach ($criteria as $cid => $crit): ?>
                                                    <th class="text-center font-monospace" style="width: 110px;">
                                                        <?= htmlspecialchars($cid, ENT_QUOTES, 'UTF-8') ?>
                                                        <div class="small fw-normal text-muted" style="font-size: 0.7rem;">div: <?= number_format($result['divisors'][$cid], 2) ?></div>
                                                    </th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($alternatives as $aid => $aname): ?>
                                                <tr>
                                                    <td class="fw-bold font-monospace"><?= htmlspecialchars($aid, ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars($aname, ENT_QUOTES, 'UTF-8') ?></td>
                                                    <?php foreach ($criteria as $cid => $crit): ?>
                                                        <td class="text-center font-monospace">
                                                            <?= number_format($result['normalized'][$aid][$cid] ?? 0.0, 4) ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        <!-- TAB 3: MATRIKS TERBOBOT -->
                        <?php elseif ($activeTab === 'terbobot'): ?>
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-transparent py-3">
                                    <strong>Matriks Ternormalisasi Terbobot (Y)</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 80px;">Kode</th>
                                                <th>Nama Alternatif</th>
                                                <?php foreach ($criteria as $cid => $crit): ?>
                                                    <th class="text-center font-monospace" style="width: 110px;">
                                                        <?= htmlspecialchars($cid, ENT_QUOTES, 'UTF-8') ?>
                                                        <div class="small fw-normal text-muted" style="font-size: 0.7rem;">w: <?= $crit['bobot'] ?></div>
                                                    </th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($alternatives as $aid => $aname): ?>
                                                <tr>
                                                    <td class="fw-bold font-monospace"><?= htmlspecialchars($aid, ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars($aname, ENT_QUOTES, 'UTF-8') ?></td>
                                                    <?php foreach ($criteria as $cid => $crit): ?>
                                                        <td class="text-center font-monospace">
                                                            <?= number_format($result['weighted'][$aid][$cid] ?? 0.0, 4) ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        <!-- TAB 4: SOLUSI IDEAL -->
                        <?php elseif ($activeTab === 'ideal'): ?>
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-transparent py-3">
                                    <strong>Solusi Ideal Positif (A<sup>+</sup>) & Negatif (A<sup>-</sup>)</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kriteria</th>
                                                <th style="width: 140px;" class="text-center">Jenis</th>
                                                <th style="width: 140px;" class="text-center">Bobot</th>
                                                <th style="width: 180px;" class="text-end font-monospace">Solusi Ideal Positif (A<sup>+</sup>)</th>
                                                <th style="width: 180px;" class="text-end font-monospace">Solusi Ideal Negatif (A<sup>-</sup>)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($criteria as $cid => $crit): ?>
                                                <tr>
                                                    <td>
                                                        <strong class="font-monospace"><?= htmlspecialchars($cid, ENT_QUOTES, 'UTF-8') ?></strong> - <?= htmlspecialchars($crit['nama'], ENT_QUOTES, 'UTF-8') ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge <?= $crit['jenis'] === 'benefit' ? 'bg-dark' : 'bg-secondary' ?> text-uppercase">
                                                            <?= htmlspecialchars($crit['jenis'], ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center"><?= $crit['bobot'] ?></td>
                                                    <td class="text-end font-monospace fw-bold"><?= number_format($result['idealPositive'][$cid], 4) ?></td>
                                                    <td class="text-end font-monospace"><?= number_format($result['idealNegative'][$cid], 4) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        <!-- TAB 5: JARAK SEPARASI -->
                        <?php elseif ($activeTab === 'jarak'): ?>
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-transparent py-3">
                                    <strong>Jarak Solusi Ideal Positif (D<sup>+</sup>) & Negatif (D<sup>-</sup>)</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 100px;">Kode</th>
                                                <th>Nama Alternatif</th>
                                                <th style="width: 180px;" class="text-end font-monospace">Jarak Positif (D<sup>+</sup>)</th>
                                                <th style="width: 180px;" class="text-end font-monospace">Jarak Negatif (D<sup>-</sup>)</th>
                                                <th style="width: 180px;" class="text-end font-monospace">Total (D<sup>+</sup> + D<sup>-</sup>)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($alternatives as $aid => $aname): ?>
                                                <?php
                                                $dp = $result['distancePositive'][$aid] ?? 0.0;
                                                $dm = $result['distanceNegative'][$aid] ?? 0.0;
                                                ?>
                                                <tr>
                                                    <td class="fw-bold font-monospace"><?= htmlspecialchars($aid, ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars($aname, ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="text-end font-monospace"><?= number_format($dp, 4) ?></td>
                                                    <td class="text-end font-monospace"><?= number_format($dm, 4) ?></td>
                                                    <td class="text-end font-monospace"><?= number_format($dp + $dm, 4) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
        <?php include 'layout/footer.php'; ?>
    </div>
    <script src="assets/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <script src="assets/vendors/simplebar/js/simplebar.min.js"></script>
</body>
</html>

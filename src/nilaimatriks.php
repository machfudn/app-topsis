<?php

declare(strict_types=1);

require_once __DIR__ . '/koneksi.php';

use App\Core\Auth;
use App\Core\Database;

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

// Fetch criteria & alternatives
$criteria = $db->query("SELECT {$cId} AS id, {$cName} AS nama, bobot, jenis FROM kriteria ORDER BY CAST(SUBSTRING({$cId} FROM 2) AS UNSIGNED) ASC, {$cId} ASC")->fetchAll();
$alternatives = $db->query("SELECT {$aId} AS id, {$aName} AS nama FROM alternatif ORDER BY CAST(SUBSTRING({$aId} FROM 2) AS UNSIGNED) ASC, {$aId} ASC")->fetchAll();

// Fetch all matrix values in one single query
$stmt = $db->query("SELECT id_alternatif, id_kriteria, nilai FROM {$tMatriks}");
$matrixRows = $stmt->fetchAll();

$matrix = [];
foreach ($matrixRows as $row) {
    $matrix[$row['id_alternatif']][$row['id_kriteria']] = (float)$row['nilai'];
}

$totalCriteria = count($criteria);
$totalAlternatives = count($alternatives);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabulasi Matriks Keputusan - App-Topsis</title>
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
                        <h4 class="fw-bold mb-0">Tabulasi Matriks Keputusan <span class="badge bg-dark rounded-pill"><?= $totalAlternatives ?>x<?= $totalCriteria ?></span></h4>
                        <span class="small text-body-secondary">Data matriks keputusan (X) dari setiap alternatif dan kriteria</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="inputnilaimatriks.php" class="btn btn-dark btn-sm">+ Input Nilai</a>
                        <a href="import.php" class="btn btn-outline-dark btn-sm">Import CSV</a>
                        <a href="nilaipreferensi.php" class="btn btn-outline-dark btn-sm">Hitung TOPSIS &rarr;</a>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">Kode</th>
                                    <th>Nama Alternatif</th>
                                    <?php foreach ($criteria as $crit): ?>
                                        <th class="text-center font-monospace" style="width: 100px;" title="<?= htmlspecialchars($crit['nama'], ENT_QUOTES, 'UTF-8') ?> (<?= $crit['jenis'] ?>)">
                                            <?= htmlspecialchars($crit['id'], ENT_QUOTES, 'UTF-8') ?>
                                            <div class="small fw-normal text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($crit['jenis'], ENT_QUOTES, 'UTF-8') ?></div>
                                        </th>
                                    <?php endforeach; ?>
                                    <th style="width: 80px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($alternatives)): ?>
                                    <tr>
                                        <td colspan="<?= $totalCriteria + 3 ?>" class="text-center py-4 text-body-secondary">Belum ada alternatif.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($alternatives as $alt): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace"><?= htmlspecialchars($alt['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($alt['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <?php foreach ($criteria as $crit): ?>
                                                <?php 
                                                $val = $matrix[$alt['id']][$crit['id']] ?? null; 
                                                ?>
                                                <td class="text-center font-monospace <?= $val === null ? 'table-warning text-danger' : '' ?>">
                                                    <?= $val !== null ? number_format((float)$val, 2) : '-' ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <td class="text-center">
                                                <a href="inputnilaimatriks.php?alt=<?= urlencode($alt['id']) ?>" class="btn btn-sm btn-outline-dark px-2 py-0" title="Edit Nilai">
                                                    ✎
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
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

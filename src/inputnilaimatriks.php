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

$message = '';
$messageType = '';

// Handle save matrix values for an alternative
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_matriks'])) {
    if (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
        $message = 'Token keamanan tidak valid.';
        $messageType = 'danger';
    } else {
        $idAlt = trim((string)($_POST['id_alt'] ?? ''));
        $scores = $_POST['nilai'] ?? [];

        if ($idAlt === '' || empty($scores) || !is_array($scores)) {
            $message = 'Pilih alternatif dan lengkapi nilai kriteria.';
            $messageType = 'danger';
        } else {
            $db->beginTransaction();
            try {
                // Upsert values
                $stmt = $db->prepare(
                    "INSERT INTO {$tMatriks} (id_alternatif, id_kriteria, nilai) 
                     VALUES (:alt, :krit, :val)
                     ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)"
                );

                foreach ($scores as $idKrit => $val) {
                    $valFloat = (float)str_replace(',', '.', trim((string)$val));
                    $stmt->execute([
                        ':alt'  => $idAlt,
                        ':krit' => $idKrit,
                        ':val'  => $valFloat,
                    ]);
                }

                $db->commit();
                $message = "Nilai matriks untuk alternatif {$idAlt} berhasil disimpan.";
                $messageType = 'success';
            } catch (\Throwable $e) {
                $db->rollBack();
                $message = 'Gagal menyimpan nilai matriks: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// Fetch criteria & alternatives
$criteria = $db->query("SELECT {$cId} AS id, {$cName} AS nama, bobot, jenis FROM kriteria ORDER BY CAST(SUBSTRING({$cId} FROM 2) AS UNSIGNED) ASC, {$cId} ASC")->fetchAll();
$alternatives = $db->query("SELECT {$aId} AS id, {$aName} AS nama FROM alternatif ORDER BY CAST(SUBSTRING({$aId} FROM 2) AS UNSIGNED) ASC, {$aId} ASC")->fetchAll();

// Selected alternative for prefilling values
$selectedAlt = trim((string)($_GET['alt'] ?? ($_POST['id_alt'] ?? ($alternatives[0]['id'] ?? ''))));
$existingValues = [];
if ($selectedAlt !== '') {
    $stmt = $db->prepare("SELECT id_kriteria, nilai FROM {$tMatriks} WHERE id_alternatif = :alt");
    $stmt->execute([':alt' => $selectedAlt]);
    $existingValues = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
}

$csrfToken = Auth::csrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai Matriks - App-Topsis</title>
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
                        <h4 class="fw-bold mb-0">Input Nilai Matriks</h4>
                        <span class="small text-body-secondary">Masukkan nilai evaluasi setiap alternatif berdasarkan kriteria</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="nilaimatriks.php" class="btn btn-outline-dark btn-sm">Lihat Tabulasi Matriks</a>
                        <a href="import.php" class="btn btn-outline-dark btn-sm">Import CSV</a>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show py-2 small" role="alert">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close py-2" data-coreui-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($alternatives) || empty($criteria)): ?>
                    <div class="alert alert-warning small">
                        Harap lengkapi <a href="kriteria.php" class="alert-link">Data Kriteria</a> dan <a href="alternatif.php" class="alert-link">Data Alternatif</a> terlebih dahulu.
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body p-4">
                            <form action="inputnilaimatriks.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                
                                <div class="mb-4">
                                    <label for="selectAlt" class="form-label small fw-bold text-uppercase">Pilih Alternatif</label>
                                    <select class="form-select" id="selectAlt" name="id_alt" onchange="window.location.href='inputnilaimatriks.php?alt=' + encodeURIComponent(this.value)">
                                        <?php foreach ($alternatives as $alt): ?>
                                            <option value="<?= htmlspecialchars($alt['id'], ENT_QUOTES, 'UTF-8') ?>" <?= $alt['id'] === $selectedAlt ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($alt['id'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($alt['nama'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text small">Pilih alternatif untuk mengisi atau mengubah nilainya.</div>
                                </div>

                                <div class="border rounded p-3 mb-4 bg-body-tertiary">
                                    <div class="fw-bold mb-3 small text-uppercase">Nilai Berdasarkan Kriteria</div>
                                    <div class="row g-3">
                                        <?php foreach ($criteria as $crit): ?>
                                            <?php 
                                            $val = $existingValues[$crit['id']] ?? ''; 
                                            ?>
                                            <div class="col-md-6">
                                                <label for="crit_<?= htmlspecialchars($crit['id'], ENT_QUOTES, 'UTF-8') ?>" class="form-label small mb-1">
                                                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($crit['id'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?= htmlspecialchars($crit['nama'], ENT_QUOTES, 'UTF-8') ?>
                                                    <span class="text-body-secondary small">(<?= htmlspecialchars($crit['jenis'], ENT_QUOTES, 'UTF-8') ?>, bobot <?= $crit['bobot'] ?>)</span>
                                                </label>
                                                <input type="number" step="any" class="form-control" 
                                                       id="crit_<?= htmlspecialchars($crit['id'], ENT_QUOTES, 'UTF-8') ?>" 
                                                       name="nilai[<?= htmlspecialchars($crit['id'], ENT_QUOTES, 'UTF-8') ?>]" 
                                                       value="<?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?>" 
                                                       required>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="nilaimatriks.php" class="btn btn-light btn-sm">Batal</a>
                                    <button type="submit" name="simpan_matriks" class="btn btn-dark btn-sm px-4">Simpan Nilai Matriks</button>
                                </div>
                            </form>
                        </div>
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

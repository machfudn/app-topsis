<?php

declare(strict_types=1);

require_once __DIR__ . '/koneksi.php';

use App\Core\Auth;
use App\Core\Database;

Auth::requireAuth();
$db = Database::getConnection();

// Detect column naming for backward compatibility
$hasNewCol = (bool)$db->query("SHOW COLUMNS FROM kriteria LIKE 'id'")->fetch();
$cId   = $hasNewCol ? 'id' : 'id_kriteria';
$cName = $hasNewCol ? 'nama' : 'nama_kriteria';

$message = '';
$messageType = '';

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (Auth::validateCsrf($_GET['token'] ?? null)) {
        $id = trim((string)$_GET['id']);
        $stmt = $db->prepare("DELETE FROM kriteria WHERE {$cId} = :id");
        $stmt->execute([':id' => $id]);
        $message = "Kriteria {$id} berhasil dihapus.";
        $messageType = 'success';
    } else {
        $message = 'Token keamanan tidak valid.';
        $messageType = 'danger';
    }
}

// Handle Add or Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_kriteria'])) {
    if (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
        $message = 'Token keamanan tidak valid.';
        $messageType = 'danger';
    } else {
        $id    = strtoupper(trim((string)($_POST['id'] ?? '')));
        $nama  = trim((string)($_POST['nama'] ?? '')));
        $bobot = (float)str_replace(',', '.', (string)($_POST['bobot'] ?? '0'));
        $jenis = strtolower(trim((string)($_POST['jenis'] ?? 'benefit'))) === 'cost' ? 'cost' : 'benefit';

        if ($id === '' || $nama === '') {
            $message = 'Kode dan nama kriteria wajib diisi.';
            $messageType = 'danger';
        } else {
            $stmt = $db->prepare(
                "INSERT INTO kriteria ({$cId}, {$cName}, bobot, jenis) 
                 VALUES (:id, :nama, :bobot, :jenis)
                 ON DUPLICATE KEY UPDATE {$cName} = VALUES({$cName}), bobot = VALUES(bobot), jenis = VALUES(jenis)"
            );
            $stmt->execute([
                ':id'    => $id,
                ':nama'  => $nama,
                ':bobot' => $bobot,
                ':jenis' => $jenis,
            ]);
            $message = "Kriteria {$id} berhasil disimpan.";
            $messageType = 'success';
        }
    }
}

// Fetch all criteria sorted naturally by numeric suffix
$stmt = $db->query("SELECT {$cId} AS id, {$cName} AS nama, bobot, jenis FROM kriteria ORDER BY CAST(SUBSTRING({$cId} FROM 2) AS UNSIGNED) ASC, {$cId} ASC");
$criteriaList = $stmt->fetchAll();
$totalCriteria = count($criteriaList);
$csrfToken = Auth::csrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kriteria (<?= $totalCriteria ?>) - App-Topsis</title>
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
                        <h4 class="fw-bold mb-0">Data Kriteria <span class="badge bg-dark rounded-pill"><?= $totalCriteria ?></span></h4>
                        <span class="small text-body-secondary">Kelola kriteria pembobotan untuk analisis TOPSIS</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-dark btn-sm" data-coreui-toggle="modal" data-coreui-target="#modalKriteria" onclick="openAddModal()">
                            + Tambah Kriteria
                        </button>
                        <a href="import.php" class="btn btn-outline-dark btn-sm">Import CSV</a>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show py-2 small" role="alert">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close py-2" data-coreui-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 100px;">Kode</th>
                                    <th>Nama Kriteria</th>
                                    <th style="width: 120px;" class="text-end">Bobot</th>
                                    <th style="width: 130px;" class="text-center">Jenis</th>
                                    <th style="width: 110px;" class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($criteriaList)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-body-secondary">Belum ada kriteria. Klik tombol Tambah Kriteria di atas.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($criteriaList as $row): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-end font-monospace"><?= number_format((float)$row['bobot'], 2) ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $row['jenis'] === 'benefit' ? 'bg-dark' : 'bg-secondary' ?> text-uppercase" style="font-size: 0.72rem;">
                                                    <?= htmlspecialchars($row['jenis'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-dark px-2 py-0" title="Ubah" 
                                                    onclick='openEditModal(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    ✎
                                                </button>
                                                <a href="kriteria.php?action=delete&id=<?= urlencode($row['id']) ?>&token=<?= $csrfToken ?>" 
                                                   class="btn btn-sm btn-outline-danger px-2 py-0" title="Hapus"
                                                   onclick="return confirm('Hapus kriteria <?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?>? Nilai matriks terkait akan ikut terhapus.');">
                                                    ✕
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

    <!-- Modal Form Kriteria -->
    <div class="modal fade" id="modalKriteria" tabindex="-1" aria-labelledby="modalKriteriaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="kriteria.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalKriteriaLabel">Tambah Kriteria</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="kriteria_id" class="form-label small fw-semibold">Kode Kriteria</label>
                            <input type="text" class="form-control font-monospace" id="kriteria_id" name="id" required>
                            <div class="form-text small">Contoh: C1, C2, C3</div>
                        </div>
                        <div class="mb-3">
                            <label for="kriteria_nama" class="form-label small fw-semibold">Nama Kriteria</label>
                            <input type="text" class="form-control" id="kriteria_nama" name="nama" required>
                        </div>
                        <div class="mb-3">
                            <label for="kriteria_bobot" class="form-label small fw-semibold">Bobot</label>
                            <input type="number" step="0.01" class="form-control" id="kriteria_bobot" name="bobot" required value="25">
                        </div>
                        <div class="mb-3">
                            <label for="kriteria_jenis" class="form-label small fw-semibold">Jenis Kriteria</label>
                            <select class="form-select" id="kriteria_jenis" name="jenis" required>
                                <option value="benefit">Benefit (Makin besar makin baik)</option>
                                <option value="cost">Cost (Makin kecil makin baik)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" name="save_kriteria" class="btn btn-dark btn-sm px-3">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <script src="assets/vendors/simplebar/js/simplebar.min.js"></script>
    <script>
        function openAddModal() {
            document.getElementById('modalKriteriaLabel').innerText = 'Tambah Kriteria';
            document.getElementById('kriteria_id').value = '';
            document.getElementById('kriteria_id').readOnly = false;
            document.getElementById('kriteria_nama').value = '';
            document.getElementById('kriteria_bobot').value = '25';
            document.getElementById('kriteria_jenis').value = 'benefit';
        }
        function openEditModal(data) {
            document.getElementById('modalKriteriaLabel').innerText = 'Ubah Kriteria ' + data.id;
            document.getElementById('kriteria_id').value = data.id;
            document.getElementById('kriteria_id').readOnly = true;
            document.getElementById('kriteria_nama').value = data.nama;
            document.getElementById('kriteria_bobot').value = data.bobot;
            document.getElementById('kriteria_jenis').value = data.jenis;
            const modalEl = document.getElementById('modalKriteria');
            const modal = coreui.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    </script>
</body>
</html>

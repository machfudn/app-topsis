<?php

declare(strict_types=1);

require_once __DIR__ . '/koneksi.php';

use App\Core\Auth;
use App\Core\Database;

Auth::requireAuth();
$db = Database::getConnection();

// Detect column naming for backward compatibility
$hasNewCol = (bool)$db->query("SHOW COLUMNS FROM alternatif LIKE 'id'")->fetch();
$aId   = $hasNewCol ? 'id' : 'id_alternatif';
$aName = $hasNewCol ? 'nama' : 'nm_alternatif';

$message = '';
$messageType = '';

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (Auth::validateCsrf($_GET['token'] ?? null)) {
        $id = trim((string)$_GET['id']);
        $stmt = $db->prepare("DELETE FROM alternatif WHERE {$aId} = :id");
        $stmt->execute([':id' => $id]);
        $message = "Alternatif {$id} berhasil dihapus.";
        $messageType = 'success';
    } else {
        $message = 'Token keamanan tidak valid.';
        $messageType = 'danger';
    }
}

// Handle Add or Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_alternatif'])) {
    if (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
        $message = 'Token keamanan tidak valid.';
        $messageType = 'danger';
    } else {
        $id   = strtoupper(trim((string)($_POST['id'] ?? '')));
        $nama = trim((string)($_POST['nama'] ?? '')));

        if ($id === '' || $nama === '') {
            $message = 'Kode dan nama alternatif wajib diisi.';
            $messageType = 'danger';
        } else {
            $stmt = $db->prepare(
                "INSERT INTO alternatif ({$aId}, {$aName}) 
                 VALUES (:id, :nama)
                 ON DUPLICATE KEY UPDATE {$aName} = VALUES({$aName})"
            );
            $stmt->execute([
                ':id'   => $id,
                ':nama' => $nama,
            ]);
            $message = "Alternatif {$id} berhasil disimpan.";
            $messageType = 'success';
        }
    }
}

// Fetch all alternatives sorted naturally by numeric suffix
$stmt = $db->query("SELECT {$aId} AS id, {$aName} AS nama FROM alternatif ORDER BY CAST(SUBSTRING({$aId} FROM 2) AS UNSIGNED) ASC, {$aId} ASC");
$alternativesList = $stmt->fetchAll();
$totalAlternatives = count($alternativesList);
$csrfToken = Auth::csrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Alternatif (<?= $totalAlternatives ?>) - App-Topsis</title>
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
                        <h4 class="fw-bold mb-0">Data Alternatif <span class="badge bg-dark rounded-pill"><?= $totalAlternatives ?></span></h4>
                        <span class="small text-body-secondary">Kelola daftar pilihan atau objek evaluasi SPK</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-dark btn-sm" data-coreui-toggle="modal" data-coreui-target="#modalAlternatif" onclick="openAddModal()">
                            + Tambah Alternatif
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
                                    <th style="width: 120px;">Kode</th>
                                    <th>Nama Alternatif</th>
                                    <th style="width: 120px;" class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($alternativesList)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-body-secondary">Belum ada alternatif. Klik tombol Tambah Alternatif di atas.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($alternativesList as $row): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace"><?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-dark px-2 py-0" title="Ubah" 
                                                    onclick='openEditModal(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    ✎
                                                </button>
                                                <a href="alternatif.php?action=delete&id=<?= urlencode($row['id']) ?>&token=<?= $csrfToken ?>" 
                                                   class="btn btn-sm btn-outline-danger px-2 py-0" title="Hapus"
                                                   onclick="return confirm('Hapus alternatif <?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?>? Nilai matriks terkait akan ikut terhapus.');">
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

    <!-- Modal Form Alternatif -->
    <div class="modal fade" id="modalAlternatif" tabindex="-1" aria-labelledby="modalAlternatifLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="alternatif.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalAlternatifLabel">Tambah Alternatif</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="alternatif_id" class="form-label small fw-semibold">Kode Alternatif</label>
                            <input type="text" class="form-control font-monospace" id="alternatif_id" name="id" required>
                            <div class="form-text small">Contoh: A1, A2, A3</div>
                        </div>
                        <div class="mb-3">
                            <label for="alternatif_nama" class="form-label small fw-semibold">Nama Alternatif</label>
                            <input type="text" class="form-control" id="alternatif_nama" name="nama" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" name="save_alternatif" class="btn btn-dark btn-sm px-3">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <script src="assets/vendors/simplebar/js/simplebar.min.js"></script>
    <script>
        function openAddModal() {
            document.getElementById('modalAlternatifLabel').innerText = 'Tambah Alternatif';
            document.getElementById('alternatif_id').value = '';
            document.getElementById('alternatif_id').readOnly = false;
            document.getElementById('alternatif_nama').value = '';
        }
        function openEditModal(data) {
            document.getElementById('modalAlternatifLabel').innerText = 'Ubah Alternatif ' + data.id;
            document.getElementById('alternatif_id').value = data.id;
            document.getElementById('alternatif_id').readOnly = true;
            document.getElementById('alternatif_nama').value = data.nama;
            const modalEl = document.getElementById('modalAlternatif');
            const modal = coreui.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    </script>
</body>
</html>

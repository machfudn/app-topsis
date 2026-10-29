<?php

declare(strict_types=1);

require_once __DIR__ . '/koneksi.php';

use App\Core\Auth;
use App\Core\CsvParser;

Auth::requireAuth();

// Handle sample CSV download
if (isset($_GET['download'])) {
    $type = $_GET['download'];
    $allowed = [
        'kriteria'   => __DIR__ . '/samples/kriteria.csv',
        'alternatif' => __DIR__ . '/samples/alternatif.csv',
        'matriks'    => __DIR__ . '/samples/matriks.csv',
    ];

    if (isset($allowed[$type]) && file_exists($allowed[$type])) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_' . $type . '.csv"');
        readfile($allowed[$type]);
        exit;
    }
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_type'])) {
    if (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
        $message = 'Token keamanan tidak valid.';
        $messageType = 'danger';
    } else {
        $type = $_POST['import_type'];
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $message = 'Gagal mengunggah file. Silakan pilih file CSV.';
            $messageType = 'danger';
        } else {
            $tmpPath = $_FILES['csv_file']['tmp_name'];
            $fileExt = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

            if ($fileExt !== 'csv' && $fileExt !== 'txt') {
                $message = 'Format file tidak didukung. Harap unggah file dengan ekstensi .csv';
                $messageType = 'danger';
            } else {
                try {
                    $imported = 0;
                    if ($type === 'kriteria') {
                        $imported = CsvParser::importCriteria($tmpPath);
                        $message = "Berhasil mengimpor {$imported} data kriteria.";
                    } elseif ($type === 'alternatif') {
                        $imported = CsvParser::importAlternatives($tmpPath);
                        $message = "Berhasil mengimpor {$imported} data alternatif.";
                    } elseif ($type === 'matriks') {
                        $imported = CsvParser::importMatrix($tmpPath);
                        $message = "Berhasil mengimpor {$imported} baris nilai matriks.";
                    }
                    $messageType = 'success';
                } catch (\Throwable $e) {
                    $message = 'Gagal memproses file CSV: ' . $e->getMessage();
                    $messageType = 'danger';
                }
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
    <title>Import Data CSV - App-Topsis</title>
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
                        <h4 class="fw-bold mb-0">Import Data CSV</h4>
                        <span class="small text-body-secondary">Impor data Kriteria, Alternatif, dan Matriks Nilai secara cepat</span>
                    </div>
                    <div>
                        <a href="index.php" class="btn btn-outline-dark btn-sm">&larr; Kembali ke Dashboard</a>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show py-2 small" role="alert">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close py-2" data-coreui-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4 mb-4">
                    <!-- Import Kriteria -->
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="fw-bold mb-1">1. Data Kriteria</h5>
                                <p class="text-body-secondary small mb-3">Kolom: id, nama, bobot, jenis (benefit/cost)</p>
                                
                                <form action="import.php" method="POST" enctype="multipart/form-data" class="mt-auto">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="import_type" value="kriteria">
                                    
                                    <div class="mb-3">
                                        <input class="form-control form-control-sm" type="file" name="csv_file" accept=".csv" required>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="import.php?download=kriteria" class="btn btn-outline-secondary btn-sm" style="font-size: 0.75rem;">
                                            Download Template
                                        </a>
                                        <button type="submit" class="btn btn-dark btn-sm px-3">Upload</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Import Alternatif -->
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="fw-bold mb-1">2. Data Alternatif</h5>
                                <p class="text-body-secondary small mb-3">Kolom: id, nama</p>
                                
                                <form action="import.php" method="POST" enctype="multipart/form-data" class="mt-auto">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="import_type" value="alternatif">
                                    
                                    <div class="mb-3">
                                        <input class="form-control form-control-sm" type="file" name="csv_file" accept=".csv" required>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="import.php?download=alternatif" class="btn btn-outline-secondary btn-sm" style="font-size: 0.75rem;">
                                            Download Template
                                        </a>
                                        <button type="submit" class="btn btn-dark btn-sm px-3">Upload</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Import Matriks Nilai -->
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="fw-bold mb-1">3. Nilai Matriks</h5>
                                <p class="text-body-secondary small mb-3">Kolom: id_alternatif, id_kriteria, nilai</p>
                                
                                <form action="import.php" method="POST" enctype="multipart/form-data" class="mt-auto">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="import_type" value="matriks">
                                    
                                    <div class="mb-3">
                                        <input class="form-control form-control-sm" type="file" name="csv_file" accept=".csv" required>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="import.php?download=matriks" class="btn btn-outline-secondary btn-sm" style="font-size: 0.75rem;">
                                            Download Template
                                        </a>
                                        <button type="submit" class="btn btn-dark btn-sm px-3">Upload</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border small">
                    <strong>Petunjuk Format CSV:</strong> File CSV dapat dibuat langsung dari Microsoft Excel atau Google Sheets dengan memilih menu <em>File &rarr; Save As / Download &rarr; Comma Separated Values (.csv)</em>. Pembatas koma (,) atau titik-koma (;) dideteksi secara otomatis.
                </div>

            </div>
        </div>
        <?php include 'layout/footer.php'; ?>
    </div>
    <script src="assets/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <script src="assets/vendors/simplebar/js/simplebar.min.js"></script>
</body>
</html>

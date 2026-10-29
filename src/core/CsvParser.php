<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class CsvParser
{
    /**
     * Import criteria from CSV stream.
     * Expected columns: id, nama, bobot, jenis
     */
    public static function importCriteria(string $filePath): int
    {
        $handle = self::openFile($filePath);
        $delimiter = self::detectDelimiter($handle);
        $db = Database::getConnection();

        $stmt = $db->prepare(
            'INSERT INTO kriteria (id, nama, bobot, jenis) 
             VALUES (:id, :nama, :bobot, :jenis)
             ON DUPLICATE KEY UPDATE nama = VALUES(nama), bobot = VALUES(bobot), jenis = VALUES(jenis)'
        );

        $count = 0;
        $isFirstRow = true;
        $db->beginTransaction();

        try {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if ($isFirstRow) {
                    $isFirstRow = false;
                    // Skip header if first column contains non-data title
                    if (isset($row[0]) && strtolower(trim($row[0])) === 'id') {
                        continue;
                    }
                }

                if (count($row) < 4 || trim((string)$row[0]) === '') {
                    continue;
                }

                $id    = trim((string)$row[0]);
                $nama  = trim((string)$row[1]);
                $bobot = (float)str_replace(',', '.', trim((string)$row[2]));
                $jenis = strtolower(trim((string)$row[3])) === 'cost' ? 'cost' : 'benefit';

                $stmt->execute([
                    ':id'    => $id,
                    ':nama'  => $nama,
                    ':bobot' => $bobot,
                    ':jenis' => $jenis,
                ]);
                $count++;
            }

            $db->commit();
            fclose($handle);
            return $count;
        } catch (\Throwable $e) {
            $db->rollBack();
            fclose($handle);
            throw new RuntimeException('Failed importing criteria: ' . $e->getMessage());
        }
    }

    /**
     * Import alternatives from CSV stream.
     * Expected columns: id, nama
     */
    public static function importAlternatives(string $filePath): int
    {
        $handle = self::openFile($filePath);
        $delimiter = self::detectDelimiter($handle);
        $db = Database::getConnection();

        $stmt = $db->prepare(
            'INSERT INTO alternatif (id, nama) VALUES (:id, :nama)
             ON DUPLICATE KEY UPDATE nama = VALUES(nama)'
        );

        $count = 0;
        $isFirstRow = true;
        $db->beginTransaction();

        try {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if ($isFirstRow) {
                    $isFirstRow = false;
                    if (isset($row[0]) && strtolower(trim($row[0])) === 'id') {
                        continue;
                    }
                }

                if (count($row) < 2 || trim((string)$row[0]) === '') {
                    continue;
                }

                $stmt->execute([
                    ':id'   => trim((string)$row[0]),
                    ':nama' => trim((string)$row[1]),
                ]);
                $count++;
            }

            $db->commit();
            fclose($handle);
            return $count;
        } catch (\Throwable $e) {
            $db->rollBack();
            fclose($handle);
            throw new RuntimeException('Failed importing alternatives: ' . $e->getMessage());
        }
    }

    /**
     * Import matrix values from CSV stream.
     * Expected columns: id_alternatif, id_kriteria, nilai
     */
    public static function importMatrix(string $filePath): int
    {
        $handle = self::openFile($filePath);
        $delimiter = self::detectDelimiter($handle);
        $db = Database::getConnection();

        $stmt = $db->prepare(
            'INSERT INTO nilai_matriks (id_alternatif, id_kriteria, nilai) 
             VALUES (:id_alt, :id_krit, :nilai)
             ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)'
        );

        $count = 0;
        $isFirstRow = true;
        $db->beginTransaction();

        try {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if ($isFirstRow) {
                    $isFirstRow = false;
                    if (isset($row[0]) && str_contains(strtolower($row[0]), 'alt')) {
                        continue;
                    }
                }

                if (count($row) < 3 || trim((string)$row[0]) === '') {
                    continue;
                }

                $idAlt  = trim((string)$row[0]);
                $idKrit = trim((string)$row[1]);
                $nilai  = (float)str_replace(',', '.', trim((string)$row[2]));

                $stmt->execute([
                    ':id_alt'  => $idAlt,
                    ':id_krit' => $idKrit,
                    ':nilai'   => $nilai,
                ]);
                $count++;
            }

            $db->commit();
            fclose($handle);
            return $count;
        } catch (\Throwable $e) {
            $db->rollBack();
            fclose($handle);
            throw new RuntimeException('Failed importing matrix values: ' . $e->getMessage());
        }
    }

    private static function openFile(string $filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new RuntimeException('Uploaded CSV file not found.');
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Cannot open CSV file.');
        }

        // Strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        return $handle;
    }

    private static function detectDelimiter($handle): string
    {
        $pos = ftell($handle);
        $line = fgets($handle);
        fseek($handle, $pos);

        if ($line === false) {
            return ',';
        }

        $semicolons = substr_count($line, ';');
        $commas     = substr_count($line, ',');

        return $semicolons > $commas ? ';' : ',';
    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/TopsisEngine.php';
require_once __DIR__ . '/core/CsvParser.php';

use App\Core\Database;

// Provide modern PDO instance
$pdo = Database::getConnection();

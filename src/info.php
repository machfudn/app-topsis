<?php

declare(strict_types=1);

require_once __DIR__ . '/koneksi.php';

use App\Core\Auth;

Auth::requireAuth();
phpinfo();

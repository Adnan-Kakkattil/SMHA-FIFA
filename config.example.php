<?php
declare(strict_types=1);

define('DB_HOST', getenv('SMHA_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('SMHA_DB_PORT') ?: '3306');
define('DB_NAME', getenv('SMHA_DB_NAME') ?: 'smha_fifa');
define('DB_USER', getenv('SMHA_DB_USER') ?: 'root');
define('DB_PASS', getenv('SMHA_DB_PASS') ?: '');

define('UPLOAD_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'players');
define('UPLOAD_URL', 'uploads/players');

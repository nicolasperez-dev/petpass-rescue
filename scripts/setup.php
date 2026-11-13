<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('ROOT_PATH', dirname(__DIR__));
require ROOT_PATH . '/src/Helpers.php';
require ROOT_PATH . '/src/Database.php';
$config = require ROOT_PATH . '/config/config.php';
if (getenv('APP_ENV') !== 'local') exit("Setup requires APP_ENV=local in .env.\n");
$password = env_value('DEMO_PASSWORD');
if (strlen($password) < 8) exit("Set DEMO_PASSWORD (at least 8 characters) in .env.\n");
$pdo = Database::connection();
if ($pdo->query('SHOW TABLES')->fetch() !== false) exit("Setup requires an empty database. Existing tables were left untouched.\n");
$pdo->exec(file_get_contents(ROOT_PATH . '/database/schema.sql'));
$seed = file_get_contents(ROOT_PATH . '/database/demo-seed.sql');
$seed = str_replace('__DEMO_PASSWORD_HASH__', password_hash($password, PASSWORD_DEFAULT), $seed);
$pdo->exec($seed);
echo "Demo database ready. Account addresses are listed in README.md.\n";

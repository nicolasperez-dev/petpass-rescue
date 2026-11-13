<?php
declare(strict_types=1);
require_once __DIR__ . '/env.php';
return [
    'app' => [
        'name' => 'PetPass Rescue',
        'url' => env_value('APP_URL', 'http://localhost:8000'),
        'base_path' => env_value('APP_BASE_PATH'),
        'environment' => env_value('APP_ENV', 'local'),
        'debug' => filter_var(env_value('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
        'timezone' => env_value('APP_TIMEZONE', 'America/Sao_Paulo'),
        'default_locale' => 'pt-BR',
        'supported_locales' => ['pt-BR', 'es'],
        'session_name' => 'PETPASSSESSID',
        'max_upload_mb' => 12,
        'install_key' => '',
    ],
    'database' => [
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => (int) env_value('DB_PORT', '3306'),
        'name' => env_value('DB_NAME', 'petpass_demo'),
        'user' => env_value('DB_USER', 'petpass_demo'),
        'password' => env_value('DB_PASSWORD'),
        'charset' => 'utf8mb4',
    ],
    'mail' => ['from' => 'demo@example.com', 'support' => 'support@example.com'],
    'features' => ['web_nfc' => true, 'demo_ads' => true, 'partner_registration' => true],
];

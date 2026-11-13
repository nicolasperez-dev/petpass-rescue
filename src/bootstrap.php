<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
require ROOT_PATH . '/src/Helpers.php';

$configFile = ROOT_PATH . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Arquivo config/config.php não encontrado. Restaure a configuração do projeto.');
}

$config = require $configFile;
date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');
ini_set('display_errors', !empty($config['app']['debug']) ? '1' : '0');
error_reporting(E_ALL);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name($config['app']['session_name'] ?? 'PETPASSSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

spl_autoload_register(static function (string $class): void {
    $paths = [
        ROOT_PATH . '/src/' . $class . '.php',
        ROOT_PATH . '/src/Controllers/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (is_file($path)) { require $path; return; }
    }
});

I18n::locale();

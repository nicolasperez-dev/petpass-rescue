<?php
declare(strict_types=1);

// Compatibilidade defensiva para hospedagens PHP sem a extensão mbstring.
// Ferozo normalmente oferece mbstring, mas estes fallbacks evitam falhas fatais.
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $value, ?string $encoding = null): int { return strlen($value); }
}
if (!function_exists('mb_substr')) {
    function mb_substr(string $value, int $start, ?int $length = null, ?string $encoding = null): string
    {
        return $length === null ? substr($value, $start) : substr($value, $start, $length);
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $value, ?string $encoding = null): string { return strtolower($value); }
}

function config(?string $key = null): mixed
{
    static $config;
    if ($config === null) {
        $config = require ROOT_PATH . '/config/config.php';
    }
    if ($key === null) return $config;
    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) return null;
        $value = $value[$segment];
    }
    return $value;
}

function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function t(string $key): string { return I18n::get($key); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(Security::csrfToken()) . '">'; }

/**
 * URL base automática para localhost, domínio principal ou subpasta no Ferozo.
 * Se app.url estiver preenchida com uma URL real, ela tem prioridade.
 */
function app_base_url(): string
{
    $configured = trim((string) config('app.url'));
    $isPlaceholder = $configured === ''
        || str_contains($configured, 'seu-dominio')
        || str_contains($configured, 'tu-dominio')
        || str_contains($configured, 'localhost:3000');

    $basePath = trim((string) config('app.base_path'), '/');

    if (!$isPlaceholder) {
        $base = rtrim($configured, '/');
        if ($basePath !== '' && !str_ends_with(parse_url($base, PHP_URL_PATH) ?: '', '/' . $basePath)) {
            $base .= '/' . $basePath;
        }
        return $base;
    }

    $forwardedProto = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]);
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || strtolower($forwardedProto) === 'https'
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
    $scheme = $https ? 'https' : 'http';
    $host = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost'))[0]);
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $host) ?: 'localhost';

    $base = $scheme . '://' . $host;
    if ($basePath !== '') $base .= '/' . $basePath;
    return rtrim($base, '/');
}

function url(string $path = ''): string
{
    if ($path !== '' && preg_match('#^(?:https?:)?//#i', $path)) return $path;
    if ($path !== '' && str_starts_with($path, 'mailto:')) return $path;
    if ($path !== '' && str_starts_with($path, 'tel:')) return $path;
    return app_base_url() . ($path === '' ? '' : '/' . ltrim($path, '/'));
}
function asset(string $path): string { return url($path); }
function redirect(string $path): never { header('Location: ' . url($path), true, 302); exit; }

function view(string $name, array $data = [], string $layout = 'layouts/app'): void
{
    extract($data, EXTR_SKIP);
    $viewFile = ROOT_PATH . '/src/Views/' . $name . '.php';
    if (!is_file($viewFile)) throw new RuntimeException('View inexistente: ' . $name);
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    require ROOT_PATH . '/src/Views/' . $layout . '.php';
}

function flash(string $type, string $message): void { $_SESSION['_flash'][$type][] = $message; }
function pull_flashes(): array { $f = $_SESSION['_flash'] ?? []; unset($_SESSION['_flash']); return $f; }
function money(int|string|null $cents): string { return 'R$ ' . number_format(((int) $cents) / 100, 2, ',', '.'); }
function selected(mixed $a, mixed $b): string { return (string) $a === (string) $b ? 'selected' : ''; }
function checked(bool|int|string|null $value): string { return (bool) $value ? 'checked' : ''; }
function current_path(): string
{
    $route = (string) ($_GET['route'] ?? '');
    if ($route !== '') return trim($route, '/');
    $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $basePath = trim((string) config('app.base_path'), '/');
    if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
        $path = trim(substr($path, strlen($basePath)), '/');
    }
    return $path;
}
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

function audit(string $action, string $entity, ?string $entityId = null, array $metadata = []): void
{
    try {
        Database::execute(
            'INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, metadata_json, ip_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [Auth::id(), $action, $entity, $entityId, json_encode($metadata, JSON_UNESCAPED_UNICODE), Security::ipHash()]
        );
    } catch (Throwable) {
        // Auditoria nunca deve quebrar uma ação principal.
    }
}

function plan_feature_limit(array $user, string $feature): ?int
{
    $row = Database::fetch(
        'SELECT pf.feature_limit FROM users u JOIN plan_features pf ON pf.plan_id = u.plan_id WHERE u.id = ? AND pf.feature_code = ? AND pf.enabled = 1 LIMIT 1',
        [(int) $user['id'], $feature]
    );
    if (!$row || $row['feature_limit'] === null) return null;
    return (int) $row['feature_limit'];
}

function user_has_feature(array $user, string $feature, ?int $petId = null): bool
{
    if (($user['role_code'] ?? '') === 'ADMIN') return true;
    if ($petId) {
        $override = Database::fetch('SELECT enabled FROM pet_feature_overrides WHERE pet_id = ? AND feature_code = ? LIMIT 1', [$petId, $feature]);
        if ($override !== null) return (bool) $override['enabled'];
    }
    $row = Database::fetch(
        'SELECT pf.enabled FROM users u JOIN plan_features pf ON pf.plan_id = u.plan_id WHERE u.id = ? AND pf.feature_code = ? LIMIT 1',
        [(int) $user['id'], $feature]
    );
    return (bool) ($row['enabled'] ?? false);
}

function require_owner_pet(int $petId): array
{
    $pet = Database::fetch('SELECT * FROM pets WHERE id = ? AND owner_user_id = ? LIMIT 1', [$petId, Auth::id()]);
    if (!$pet) { http_response_code(404); exit('Pet não encontrado.'); }
    return $pet;
}

function public_pet_url(array $pet): string { return url('/pet/' . $pet['public_id']); }
function tag_url(array $tag, string $source = 'qr'): string { return url('/tag/' . $tag['public_code'] . '?src=' . rawurlencode($source)); }

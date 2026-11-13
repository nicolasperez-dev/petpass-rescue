<?php
declare(strict_types=1);

final class I18n
{
    private static array $strings = [
        'pt-BR' => [
            'home' => 'Início', 'login' => 'Entrar', 'logout' => 'Sair', 'my_pets' => 'Meus pets',
            'map' => 'Mapa pet', 'partners' => 'Lojas parceiras', 'plans' => 'Planos', 'save' => 'Salvar',
            'care' => 'Cuidado', 'love' => 'Amor', 'protection' => 'Proteção', 'happiness' => 'Felicidade',
        ],
        'es' => [
            'home' => 'Inicio', 'login' => 'Ingresar', 'logout' => 'Salir', 'my_pets' => 'Mis mascotas',
            'map' => 'Mapa pet', 'partners' => 'Comercios socios', 'plans' => 'Planes', 'save' => 'Guardar',
            'care' => 'Cuidado', 'love' => 'Amor', 'protection' => 'Protección', 'happiness' => 'Felicidad',
        ],
    ];

    public static function locale(): string
    {
        $supported = config('app.supported_locales');
        $requested = $_GET['lang'] ?? $_SESSION['locale'] ?? null;
        if ($requested && in_array($requested, $supported, true)) {
            $_SESSION['locale'] = $requested;
            return $requested;
        }
        $browser = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2));
        $locale = $browser === 'es' ? 'es' : config('app.default_locale');
        $_SESSION['locale'] = $locale;
        return $locale;
    }

    public static function get(string $key): string
    {
        $locale = self::locale();
        return self::$strings[$locale][$key] ?? self::$strings['pt-BR'][$key] ?? $key;
    }
}

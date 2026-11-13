<?php
$title = $title ?? 'Minha família';
$flashes = pull_flashes();
$user = Auth::user();
$path = current_path();
$active = static fn(string $route): string => ($path === trim($route, '/') || str_starts_with($path, trim($route, '/') . '/')) ? 'active' : '';
?>
<!doctype html>
<html lang="<?=e(I18n::locale())?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#08a5a3">
    <title><?=e($title)?> - PetPass</title>
    <link rel="icon" href="<?=asset('assets/img/favicon.svg')?>">
    <link rel="stylesheet" href="<?=asset('assets/css/app.css')?>">
    <script>window.PETPASS={baseUrl:<?=json_encode(app_base_url())?>,csrf:<?=json_encode(Security::csrfToken())?>,locale:<?=json_encode(I18n::locale())?>};</script>
</head>
<body class="dashboard-body">
<aside class="sidebar owner-sidebar">
    <a class="brand" href="<?=url('/owner')?>"><span class="brand-icon">🐾</span><span>PetPass <b>Família</b></span></a>
    <div class="emotion-card"><span>❤️</span><p>Um lugar feito para cuidar de quem é parte da sua família.</p></div>
    <nav>
        <a class="<?=$active('owner')?>" href="<?=url('/owner')?>">🏠 Visão geral</a>
        <a class="<?=$active('owner/pets')?>" href="<?=url('/owner/pets')?>">🐶 Meus filhos pet</a>
        <a class="<?=$active('owner/map')?>" href="<?=url('/owner/map')?>">📍 Mapa e locais pet</a>
        <a class="<?=$active('owner/plans')?>" href="<?=url('/owner/plans')?>">✨ Planos e benefícios</a>
        <a href="<?=url('/')?>">🌐 Início público</a>
    </nav>
    <div class="sidebar-user">
        <strong><?=e($user['name'])?></strong>
        <span><?=e($user['plan_name'] ?? 'Gratuito')?></span>
        <form method="post" action="<?=url('/logout')?>"><?=csrf_field()?><button class="btn btn-ghost btn-block">Sair</button></form>
    </div>
</aside>
<main class="dashboard-main">
    <header class="dashboard-top">
        <button class="mobile-toggle" type="button" data-sidebar-toggle aria-label="Abrir menu">☰</button>
        <div><small>Cuidado, presença e tranquilidade</small><h1><?=e($title)?></h1></div>
        <div class="dashboard-top-actions">
            <a class="btn btn-soft desktop-only" href="<?=url('/')?>">Ver início</a>
            <a class="btn btn-soft desktop-only" href="<?=url('/owner/map')?>">Abrir mapa</a>
            <a class="btn btn-primary" href="<?=url('/owner/pets/create')?>">+ Adicionar pet</a>
        </div>
    </header>
    <?php foreach($flashes as $type=>$messages): foreach($messages as $message): ?>
        <div class="flash flash-<?=e($type)?>"><?=e($message)?></div>
    <?php endforeach; endforeach; ?>
    <?=$content?>
</main>
<?php require ROOT_PATH . '/src/Views/layouts/bot.php'; ?>
<script src="<?=asset('assets/js/app.js')?>" defer></script>
</body>
</html>

<?php $title = $title ?? 'PetPass Rescue'; $flashes = pull_flashes(); $loggedUser = Auth::user(); ?>
<!doctype html>
<html lang="<?=e(I18n::locale())?>">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#08a5a3">
    <title><?=e($title)?> - PetPass Rescue</title>
    <link rel="icon" href="<?=asset('assets/img/favicon.svg')?>">
    <link rel="stylesheet" href="<?=asset('assets/css/app.css')?>">
    <script>window.PETPASS={baseUrl:<?=json_encode(app_base_url())?>,csrf:<?=json_encode(Security::csrfToken())?>,locale:<?=json_encode(I18n::locale())?>};</script>
</head>
<body class="public-body">
<header class="public-header">
    <a class="brand" href="<?=url('/')?>"><span class="brand-icon">🐾</span><span>PetPass <b>Rescue</b></span></a>
    <nav class="public-nav"><a href="<?=url('/')?>">Início</a><a href="<?=url('/map')?>">Mapa pet</a><?php if($loggedUser):?><a class="btn btn-soft" href="<?=url($loggedUser['role_code']==='OWNER'?'/owner':($loggedUser['role_code']==='ADMIN'?'/admin':'/partner'))?>">Meu painel</a><?php endif;?></nav>
    <div><a class="lang" href="?lang=pt-BR">PT</a><a class="lang" href="?lang=es">ES</a></div>
</header>
<?php foreach($flashes as $type=>$messages): foreach($messages as $message):?><div class="flash flash-<?=e($type)?>"><?=e($message)?></div><?php endforeach; endforeach;?>
<?=$content?>
<?php require ROOT_PATH.'/src/Views/layouts/bot.php'; ?>
<script src="<?=asset('assets/js/app.js')?>" defer></script>
</body>
</html>

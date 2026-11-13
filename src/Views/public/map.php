<?php
$typeLabels = ['VETERINARY'=>'Veterinária','PETSHOP'=>'Pet shop','HOTEL'=>'Hotel pet friendly','RESTAURANT'=>'Restaurante pet friendly','OTHER'=>'Outro serviço'];
$mapPlaces = array_map(static function(array $place): array {
    $address = trim(implode(', ', array_filter([$place['address'] ?? null, $place['neighborhood'] ?? null, $place['city'] ?? null, $place['state'] ?? null])));
    $place['navigation_url'] = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address !== '' ? $address : (string) $place['name']);
    $place['detail_url'] = !empty($place['is_partner']) ? url('/partners/' . $place['slug']) : ($place['website'] ?: $place['source_url']);
    return $place;
}, $places);
?>
<link rel="stylesheet" href="<?=asset('assets/vendor/leaflet/leaflet.css')?>">
<main class="section map-page">
    <div class="section-heading left"><span class="eyebrow">Bombinhas até Itajaí</span><h1>Mapa PetPass</h1><p>Veterinárias, emergências 24h, pet shops e hospedagens pet friendly em um único guia.</p></div>
    <form class="filter-bar" method="get" action="<?=url('/map')?>">
        <input name="q" value="<?=e($query)?>" placeholder="Nome, serviço ou bairro">
        <select name="type"><option value="">Todos</option><?php foreach($typeLabels as $value=>$label):?><option value="<?=$value?>" <?=selected($type,$value)?>><?=$label?></option><?php endforeach;?></select>
        <select name="city"><option value="">Todas as cidades</option><?php foreach($cities as $item):?><option value="<?=e($item['city'])?>" <?=selected($city,$item['city'])?>><?=e($item['city'])?></option><?php endforeach;?></select>
        <label class="inline-check map-check"><input type="checkbox" name="emergency_24h" value="1" <?=checked($only24h)?>> Somente 24h</label>
        <button class="btn btn-primary">Buscar</button><button class="btn btn-soft" type="button" data-near-me>Usar minha localização</button>
    </form>
    <div class="directory-note">Os estabelecimentos externos aparecem como guia informativo e não representam parceria comercial. Confirme horários, plantão e política pet diretamente com o local.</div>
    <div class="map-layout">
        <div id="petpass-map" class="map-box" data-places='<?=e(json_encode($mapPlaces,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?>'></div>
        <div class="map-list" data-distance-list>
            <?php foreach($mapPlaces as $place): $address=trim(implode(', ',array_filter([$place['address'],$place['neighborhood'],$place['city'],$place['state']]))); ?>
            <article class="map-result directory-card" data-distance-item data-lat="<?=e($place['latitude'])?>" data-lng="<?=e($place['longitude'])?>">
                <div class="directory-main"><div class="directory-badges"><span class="partner-badge"><?=e($typeLabels[$place['partner_type']]??$place['partner_type'])?></span><?php if($place['emergency_24h']):?><span class="status danger">24h</span><?php endif;?></div><h3><?=e($place['name'])?></h3><?php if($place['subtype']):?><p class="directory-subtype"><?=e($place['subtype'])?></p><?php endif;?><p>📍 <?=e($address)?></p><span class="distance-label" data-distance-label></span></div>
                <div class="directory-actions"><a class="btn btn-primary btn-sm" href="<?=e($place['navigation_url'])?>" target="_blank" rel="noopener">Como chegar</a><?php if($place['detail_url']):?><a class="btn btn-soft btn-sm" href="<?=e($place['detail_url'])?>" target="<?=$place['is_partner']?'_self':'_blank'?>" rel="noopener">Informações</a><?php endif;?></div>
            </article>
            <?php endforeach;?>
            <?php if(!$mapPlaces):?><div class="empty-state"><span>🔎</span><p>Nenhum local encontrado com estes filtros.</p></div><?php endif;?>
        </div>
    </div>
</main>
<script src="<?=asset('assets/vendor/leaflet/leaflet.js')?>" defer></script>

<?php
$title = 'Mapa e cuidados perto de você';
$typeLabels = ['VETERINARY'=>'Veterinária','PETSHOP'=>'Pet shop','HOTEL'=>'Hotel pet friendly','RESTAURANT'=>'Restaurante pet friendly','OTHER'=>'Outro serviço'];
$mapPlaces = array_map(static function(array $place): array {
    $address = trim(implode(', ', array_filter([$place['address'] ?? null, $place['neighborhood'] ?? null, $place['city'] ?? null, $place['state'] ?? null])));
    $place['navigation_url'] = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address !== '' ? $address : (string) $place['name']);
    $place['detail_url'] = !empty($place['is_partner']) ? url('/partners/' . $place['slug']) : ($place['website'] ?: $place['source_url']);
    return $place;
}, $places);
?>
<link rel="stylesheet" href="<?=asset('assets/vendor/leaflet/leaflet.css')?>">
<section class="welcome-banner compact"><div><span class="eyebrow">Bombinhas → Itajaí</span><h2>Cuidados reais, mais perto da sua família.</h2><p>Consulte veterinárias, emergências 24h, pet shops e hospedagens pet friendly. Os locais externos não são parceiros PetPass e devem ter horários e regras confirmados antes da visita.</p></div><div class="heart-pulse">📍</div></section>
<form class="filter-bar owner-map-filters" method="get" action="<?=url('/owner/map')?>">
    <input name="q" value="<?=e($query)?>" placeholder="Buscar nome, serviço ou bairro">
    <select name="type"><option value="">Todos os tipos</option><?php foreach($typeLabels as $value=>$label):?><option value="<?=$value?>" <?=selected($type,$value)?>><?=$label?></option><?php endforeach;?></select>
    <select name="city"><option value="">Todas as cidades</option><?php foreach($cities as $item):?><option value="<?=e($item['city'])?>" <?=selected($city,$item['city'])?>><?=e($item['city'])?></option><?php endforeach;?></select>
    <label class="inline-check map-check"><input type="checkbox" name="emergency_24h" value="1" <?=checked($only24h)?>> Somente 24h</label>
    <button class="btn btn-primary">Filtrar</button>
    <button class="btn btn-soft" type="button" data-near-me>Usar minha localização</button>
</form>
<div class="directory-note"><b>Importante:</b> “24h” indica atendimento de emergência divulgado pela própria empresa ou fonte consultada. Telefone antes de sair, principalmente em feriados.</div>
<div class="map-layout owner-map-layout">
    <div id="petpass-map" class="map-box" data-places='<?=e(json_encode($mapPlaces,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?>'></div>
    <div class="map-list" data-distance-list>
        <?php foreach($mapPlaces as $place):
            $address = trim(implode(', ', array_filter([$place['address'],$place['neighborhood'],$place['city'],$place['state']])));
            $contact = $place['whatsapp'] ?: $place['phone'];
        ?>
        <article class="map-result directory-card" data-distance-item data-lat="<?=e($place['latitude'])?>" data-lng="<?=e($place['longitude'])?>">
            <div class="directory-main">
                <div class="directory-badges"><span class="partner-badge"><?=e($typeLabels[$place['partner_type']] ?? $place['partner_type'])?></span><?php if($place['emergency_24h']):?><span class="status danger">Emergência 24h</span><?php endif;?><?php if($place['is_partner']):?><span class="status success">Parceiro PetPass</span><?php else:?><span class="status neutral">Guia externo</span><?php endif;?></div>
                <h3><?=e($place['name'])?></h3>
                <?php if($place['subtype']):?><p class="directory-subtype"><?=e($place['subtype'])?></p><?php endif;?>
                <p>📍 <?=e($address)?></p>
                <?php if($place['services_text']):?><p class="directory-services"><?=e($place['services_text'])?></p><?php endif;?>
                <small>Dados revisados em <?=e($place['verified_at'] ?: 'data não informada')?><?=($place['coordinate_precision']??'')==='APPROXIMATE'?' · marcador aproximado pela direção':''?></small>
                <span class="distance-label" data-distance-label></span>
            </div>
            <div class="directory-actions">
                <a class="btn btn-primary btn-sm" href="<?=e($place['navigation_url'])?>" target="_blank" rel="noopener">Como chegar</a>
                <?php if($contact):?><a class="btn btn-soft btn-sm" href="https://wa.me/<?=e(preg_replace('/\D/','',(string)$contact))?>" target="_blank" rel="noopener">Contato</a><?php endif;?>
                <?php if($place['detail_url']):?><a class="btn btn-ghost btn-sm" href="<?=e($place['detail_url'])?>" target="<?=$place['is_partner']?'_self':'_blank'?>" rel="noopener">Ver informações</a><?php endif;?>
            </div>
        </article>
        <?php endforeach;?>
        <?php if(!$mapPlaces):?><div class="empty-state"><span>🔎</span><h3>Nenhum local encontrado</h3><p>Remova algum filtro ou procure outra cidade.</p></div><?php endif;?>
    </div>
</div>
<script src="<?=asset('assets/vendor/leaflet/leaflet.js')?>" defer></script>

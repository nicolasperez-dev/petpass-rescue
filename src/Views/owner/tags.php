<?php
$title = 'QR e NFC de ' . $pet['name'];
$mainQrUrl = tag_url($primaryTag, 'qr');
$mainNfcUrl = tag_url($primaryTag, 'nfc');
?>
<section class="dashboard-grid two">
    <div class="panel-card qr-main-card">
        <span class="eyebrow">Identidade única e permanente</span>
        <h2>QR próprio de <?=e($pet['name'])?></h2>
        <p>Este QR abre a rota segura da tag, registra a leitura e depois leva ao perfil público. Você pode alterar foto, telefone e informações sem imprimir outro código.</p>
        <div class="identity-strip"><b>ID da mascota</b><code><?=e($pet['public_id'])?></code><b>ID da tag</b><code><?=e($primaryTag['public_code'])?></code></div>
        <div class="qr-stage" data-qr-text="<?=e($mainQrUrl)?>">
            <div data-qr-target></div>
            <p class="qr-loading" data-qr-loading>Gerando QR seguro…</p>
        </div>
        <code class="url-code" data-copy-value><?=e($mainQrUrl)?></code>
        <div class="button-row">
            <button class="btn btn-primary" type="button" data-download-qr="<?=e($pet['name'])?>">Baixar QR em PNG</button>
            <button class="btn btn-soft" type="button" data-copy-text="<?=e($mainQrUrl)?>">Copiar link</button>
            <a class="btn btn-ghost" target="_blank" rel="noopener" href="<?=e($mainQrUrl)?>">Testar QR</a>
        </div>
        <div class="success-note">✓ O QR e o NFC podem usar a mesma tag e permanecerão válidos enquanto ela estiver ativa.</div>
    </div>
    <div class="panel-card">
        <span class="eyebrow">Primeira ativação</span>
        <h2>Vincular tag física</h2>
        <p>Use o código público e o código secreto recebidos na embalagem. Depois da ativação, o segredo é eliminado e a tag fica associada exclusivamente a <?=e($pet['name'])?>.</p>
        <form method="post" action="<?=url('/owner/pets/'.$pet['id'].'/claim-tag')?>">
            <?=csrf_field()?>
            <label>Código público<input name="public_code" placeholder="TAG-XXXXXXXX" autocomplete="off" required></label>
            <label>Código secreto<input name="activation_code" inputmode="numeric" autocomplete="one-time-code" placeholder="Código da embalagem" required></label>
            <button class="btn btn-primary btn-block">Vincular a <?=e($pet['name'])?></button>
        </form>
        <hr>
        <h3>Programar NFC principal</h3>
        <p>Grave esta mesma identidade no chip NFC. Em Android/Chrome com HTTPS, PetPass tentará gravar diretamente. Em outros aparelhos, copiará a URL para usar no NFC Tools.</p>
        <code class="url-code"><?=e($mainNfcUrl)?></code>
        <button class="btn btn-soft btn-block" type="button" data-program-nfc="<?=e($mainNfcUrl)?>">Programar ou copiar URL NFC</button>
    </div>
</section>

<section class="dashboard-section">
    <div class="section-row"><div><h2>Tags vinculadas</h2><p>Uma mascota pode usar várias tags: coleira, peitoral ou tag de viagem.</p></div></div>
    <div class="tag-grid">
        <?php foreach($tags as $tag): $qrUrl=tag_url($tag,'qr'); $nfcUrl=tag_url($tag,'nfc'); ?>
        <article class="tag-card">
            <div>
                <span class="status <?=$tag['status']==='ACTIVE'?'success':'warning'?>"><?=e($tag['status'])?></span>
                <h3><?=e($tag['label'] ?: 'Tag PetPass')?></h3>
                <code><?=e($tag['public_code'])?></code>
                <p><?=e($tag['scans'])?> leituras · NFC <?=$tag['nfc_programmed_at']?'programado':'pendente'?></p>
                <small>Destino: <?=e($qrUrl)?></small>
            </div>
            <div class="qr-small" data-qr-text="<?=e($qrUrl)?>" data-qr-target></div>
            <div class="button-row">
                <a class="btn btn-ghost btn-sm" href="<?=e($qrUrl)?>" target="_blank" rel="noopener">Testar</a>
                <button class="btn btn-soft btn-sm" type="button" data-program-nfc="<?=e($nfcUrl)?>">Programar NFC</button>
                <?php if(!$tag['nfc_programmed_at']):?><form method="post" action="<?=url('/owner/pets/'.$pet['id'].'/tags/'.$tag['id'].'/nfc-programmed')?>"><?=csrf_field()?><button class="btn btn-ghost btn-sm">Marcar verificado</button></form><?php endif;?>
            </div>
        </article>
        <?php endforeach;?>
    </div>
</section>

<section class="dashboard-section">
    <h2>Últimas leituras</h2>
    <div class="table-wrap"><table><thead><tr><th>Tag</th><th>Origem</th><th>Data</th><th>Localização</th></tr></thead><tbody>
    <?php foreach($recentScans as $scan):?><tr><td><?=e($scan['label'])?></td><td><?=e($scan['source'])?></td><td><?=e($scan['scanned_at'])?></td><td><?=($scan['latitude']&&$scan['longitude'])?e($scan['latitude'].', '.$scan['longitude']):'Não informada'?></td></tr><?php endforeach;?>
    <?php if(!$recentScans):?><tr><td colspan="4">Ainda não há leituras. Use “Testar QR” para fazer a primeira.</td></tr><?php endif;?>
    </tbody></table></div>
</section>
<script src="<?=asset('assets/vendor/qrcode/qrcode.min.js')?>" defer></script>

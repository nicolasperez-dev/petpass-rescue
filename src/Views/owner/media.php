<?php
$title = 'Fotos, áudios e memórias de ' . $pet['name'];
$serverLimit = Security::serverUploadLimitBytes();
$serverLimitLabel = $serverLimit > 0 ? Security::humanBytes($serverLimit) : 'definido pelo servidor';
?>
<section class="welcome-banner compact"><div><span class="eyebrow">Memórias e informação</span><h2>Cada foto conta um pedaço da história de <?=e($pet['name'])?>.</h2><p>Envie da galeria, tire uma foto na hora, grave sua voz ou guarde documentos importantes.</p></div><div class="heart-pulse">📷</div></section>
<div class="upload-health"><span>✓ Pasta de destino preparada automaticamente</span><span>Limite atual do PHP: <?=e($serverLimitLabel)?></span><span>Formatos validados por conteúdo, não apenas extensão</span></div>

<section class="media-upload-grid">
    <article class="panel-card upload-card">
        <span class="upload-icon">🖼️</span><h2>Escolher uma foto</h2><p>JPG, PNG, WEBP ou GIF. Ideal para a galeria.</p>
        <form method="post" enctype="multipart/form-data" action="<?=url('/owner/pets/'.$pet['id'].'/media')?>">
            <?=csrf_field()?><input type="hidden" name="media_type" value="PHOTO">
            <label>Foto da galeria<input type="file" name="media_file" accept="image/jpeg,image/png,image/webp,image/gif" required></label>
            <label>Legenda<input name="caption" placeholder="Ex.: Primeiro dia na praia"></label>
            <label class="check"><input type="checkbox" name="is_public" value="1"> Mostrar no perfil público</label>
            <label class="check"><input type="checkbox" name="use_as_main" value="1"> Usar como foto principal se ainda não houver uma</label>
            <button class="btn btn-primary btn-block">Enviar foto</button>
        </form>
    </article>

    <article class="panel-card upload-card camera-card">
        <span class="upload-icon">📸</span><h2>Tirar uma foto agora</h2><p>No celular, abre a câmera traseira diretamente.</p>
        <form method="post" enctype="multipart/form-data" action="<?=url('/owner/pets/'.$pet['id'].'/media')?>">
            <?=csrf_field()?><input type="hidden" name="media_type" value="PHOTO">
            <label>Abrir câmera<input type="file" name="media_file" accept="image/*" capture="environment" required></label>
            <label>Legenda<input name="caption" placeholder="O que estava acontecendo?"></label>
            <label class="check"><input type="checkbox" name="is_public" value="1"> Mostrar no perfil público</label>
            <label class="check"><input type="checkbox" name="use_as_main" value="1" checked> Usar como foto principal se não houver uma</label>
            <button class="btn btn-primary btn-block">Guardar foto</button>
        </form>
    </article>

    <article class="panel-card upload-card premium-upload" data-audio-recorder>
        <span class="upload-icon">🎙️</span><h2>Mensagem de voz <small>Plus</small></h2><p>Grave uma mensagem para quem encontrar <?=e($pet['name'])?> ou envie um áudio existente.</p>
        <form method="post" enctype="multipart/form-data" action="<?=url('/owner/pets/'.$pet['id'].'/media')?>">
            <?=csrf_field()?><input type="hidden" name="media_type" value="AUDIO">
            <div class="recorder-actions"><button type="button" class="btn btn-soft" data-record-start>🎙 Gravar</button><button type="button" class="btn btn-danger" data-record-stop disabled>Parar</button><button type="button" class="btn btn-ghost" data-record-reset>Limpar</button></div>
            <audio controls data-record-preview hidden></audio>
            <label>Ou escolher áudio<input type="file" name="media_file" accept="audio/mpeg,audio/mp4,audio/webm,audio/ogg,audio/wav" data-audio-file></label>
            <label>Legenda<input name="caption" placeholder="Mensagem da família"></label>
            <label class="check"><input type="checkbox" name="is_public" value="1" checked> Disponível no perfil público</label>
            <button class="btn btn-primary btn-block">Guardar áudio</button>
        </form>
    </article>

    <article class="panel-card upload-card premium-upload">
        <span class="upload-icon">📄</span><h2>Vídeo ou documento <small>Plus</small></h2><p>Guarde vídeos MP4/WEBM ou documentos PDF de saúde.</p>
        <form method="post" enctype="multipart/form-data" action="<?=url('/owner/pets/'.$pet['id'].'/media')?>">
            <?=csrf_field()?>
            <label>Tipo<select name="media_type"><option value="VIDEO">Vídeo</option><option value="DOCUMENT">Documento de saúde</option></select></label>
            <label>Arquivo<input type="file" name="media_file" accept="video/mp4,video/webm,application/pdf,image/jpeg,image/png" required></label>
            <label>Descrição<input name="caption"></label>
            <label class="check"><input type="checkbox" name="is_public" value="1"> Mostrar no perfil público</label>
            <button class="btn btn-primary btn-block">Guardar arquivo</button>
        </form>
    </article>
</section>

<section class="dashboard-section">
    <div class="section-row"><div><h2>Galeria de <?=e($pet['name'])?></h2><p>Você pode remover qualquer item quando quiser.</p></div><a class="btn btn-soft" href="<?=url('/owner/plans')?>">Comparar planos</a></div>
    <div class="media-grid manager">
        <?php foreach($media as $item):?><article class="media-item">
            <?php if($item['media_type']==='PHOTO'):?><img src="<?=asset($item['file_path'])?>" alt="<?=e($item['caption']?:'Foto de '.$pet['name'])?>">
            <?php elseif($item['media_type']==='AUDIO'):?><audio controls preload="metadata" src="<?=asset($item['file_path'])?>"></audio>
            <?php elseif($item['media_type']==='VIDEO'):?><video controls preload="metadata" src="<?=asset($item['file_path'])?>"></video>
            <?php else:?><a class="document-link" href="<?=asset($item['file_path'])?>" target="_blank" rel="noopener">📄 Abrir documento</a><?php endif;?>
            <p><?=e($item['caption'] ?: $item['original_name'])?></p>
            <span class="status <?=$item['is_public']?'success':'neutral'?>"><?=$item['is_public']?'Público':'Privado'?></span>
            <form method="post" action="<?=url('/owner/pets/'.$pet['id'].'/media/'.$item['id'].'/delete')?>" onsubmit="return confirm('Eliminar este arquivo?')"><?=csrf_field()?><button class="btn btn-danger btn-block">Eliminar</button></form>
        </article><?php endforeach;?>
        <?php if(!$media):?><div class="empty-state"><span>📷</span><h3>A galeria ainda está esperando a primeira memória.</h3><p>Envie uma foto usando uma das opções acima.</p></div><?php endif;?>
    </div>
</section>

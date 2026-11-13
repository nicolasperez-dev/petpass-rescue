(() => {
  'use strict';
  const $ = (s, root = document) => root.querySelector(s);
  const $$ = (s, root = document) => [...root.querySelectorAll(s)];

  const sidebarToggle = $('[data-sidebar-toggle]');
  if (sidebarToggle) sidebarToggle.addEventListener('click', () => $('.sidebar')?.classList.toggle('open'));

  $$('[data-filter-input]').forEach(filterInput => filterInput.addEventListener('input', () => {
    const q = normalize(filterInput.value);
    const list = filterInput.closest('section,main,.dashboard-section') || document;
    $$('[data-filter-item]', list).forEach(item => item.hidden = !normalize(item.dataset.filterItem || '').includes(q));
  }));

  $$('[data-tabs]').forEach(tabs => {
    const container = tabs.parentElement;
    $$('[data-tab]', tabs).forEach(button => button.addEventListener('click', () => {
      $$('[data-tab]', tabs).forEach(b => b.classList.toggle('active', b === button));
      $$('[data-panel]', container).forEach(panel => panel.classList.toggle('active', panel.dataset.panel === button.dataset.tab));
    }));
  });

  $$('[data-photo-input]').forEach(photoInput => photoInput.addEventListener('change', () => {
    const file = photoInput.files?.[0]; if (!file) return;
    if (!file.type.startsWith('image/')) return alert('Escolha um arquivo de imagem válido.');
    const image = document.createElement('img'); image.src = URL.createObjectURL(file); image.alt = 'Prévia da foto';
    const preview = $('[data-photo-preview]'); if (!preview) return;
    preview.innerHTML = ''; preview.appendChild(image);
  }));

  const species = $('[data-species]');
  const breed = $('[data-breed]');
  if (species && breed) {
    const apply = () => $$('option[data-species-id]', breed).forEach(o => {
      o.hidden = Boolean(species.value && o.dataset.speciesId !== species.value);
    });
    species.addEventListener('change', () => { breed.value = ''; apply(); });
    apply();
  }

  $$('[data-audio-recorder]').forEach(recorder => {
    const start = $('[data-record-start]', recorder);
    const stop = $('[data-record-stop]', recorder);
    const reset = $('[data-record-reset]', recorder);
    const preview = $('[data-record-preview]', recorder);
    const fileInput = $('[data-audio-file]', recorder);
    if (!start || !stop || !reset || !preview || !fileInput) return;
    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
      start.disabled = true;
      start.textContent = 'Gravação não disponível';
      return;
    }
    let mediaRecorder = null, chunks = [], stream = null;
    start.addEventListener('click', async () => {
      try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        chunks = [];
        const preferred = ['audio/webm;codecs=opus','audio/webm','audio/mp4'].find(t => MediaRecorder.isTypeSupported?.(t));
        mediaRecorder = preferred ? new MediaRecorder(stream, { mimeType: preferred }) : new MediaRecorder(stream);
        mediaRecorder.ondataavailable = e => { if (e.data?.size) chunks.push(e.data); };
        mediaRecorder.onstop = () => {
          const mime = mediaRecorder?.mimeType || 'audio/webm';
          const blob = new Blob(chunks, { type: mime });
          preview.src = URL.createObjectURL(blob); preview.hidden = false;
          const ext = mime.includes('mp4') ? 'm4a' : mime.includes('ogg') ? 'ogg' : 'webm';
          try {
            const file = new File([blob], `petpass-audio-${Date.now()}.${ext}`, { type: mime });
            const dt = new DataTransfer(); dt.items.add(file); fileInput.files = dt.files;
          } catch (_) {
            alert('O áudio foi gravado, mas este navegador não consegue anexá-lo automaticamente. Baixe ou escolha um arquivo manualmente.');
          }
          stream?.getTracks().forEach(t => t.stop());
        };
        mediaRecorder.start();
        start.disabled = true; stop.disabled = false; start.textContent = '● Gravando…';
      } catch (_) {
        alert('Não foi possível acessar o microfone. Use HTTPS e autorize a permissão do navegador.');
      }
    });
    stop.addEventListener('click', () => {
      if (mediaRecorder?.state === 'recording') mediaRecorder.stop();
      start.disabled = false; stop.disabled = true; start.textContent = '🎙 Gravar';
    });
    reset.addEventListener('click', () => {
      if (mediaRecorder?.state === 'recording') mediaRecorder.stop();
      stream?.getTracks().forEach(t => t.stop());
      preview.pause(); preview.hidden = true; preview.removeAttribute('src'); fileInput.value = ''; chunks = [];
      start.disabled = false; stop.disabled = true; start.textContent = '🎙 Gravar';
    });
  });

  const modal = $('[data-sighting-modal]');
  $('[data-open-sighting]')?.addEventListener('click', () => { if (modal) modal.hidden = false; });
  $('[data-close-sighting]')?.addEventListener('click', () => { if (modal) modal.hidden = true; });
  $('[data-capture-location]')?.addEventListener('click', function () {
    if (!navigator.geolocation) return alert('Seu navegador não disponibiliza localização. Você ainda pode enviar a mensagem.');
    this.disabled = true; this.textContent = 'Obtendo localização…';
    navigator.geolocation.getCurrentPosition(pos => {
      const form = $('[data-sighting-form]');
      if (form) { form.latitude.value = pos.coords.latitude; form.longitude.value = pos.coords.longitude; form.location_consent.checked = true; }
      this.textContent = '✓ Localização adicionada';
    }, () => { this.disabled = false; this.textContent = 'Usar minha localização'; alert('Localização não autorizada. Envie a mensagem sem ela.'); }, { enableHighAccuracy: true, timeout: 12000 });
  });

  $('[data-fill-location]')?.addEventListener('click', function () {
    if (!navigator.geolocation) return alert('Localização não disponível neste navegador.');
    navigator.geolocation.getCurrentPosition(pos => {
      const form = this.closest('form'); if (!form) return;
      form.latitude.value = pos.coords.latitude; form.longitude.value = pos.coords.longitude; this.textContent = '✓ Localização preenchida';
    }, () => alert('Não foi possível obter sua localização.'));
  });

  const renderQrs = (attempt = 0) => {
    const targets = $$('[data-qr-target]');
    if (!targets.length) return;
    if (!window.QRCode) {
      if (attempt < 30) return setTimeout(() => renderQrs(attempt + 1), 150);
      targets.forEach(target => target.innerHTML = '<p class="qr-error">Não foi possível carregar o gerador. Confirme se assets/vendor/qrcode/qrcode.min.js foi enviado ao host.</p>');
      return;
    }
    targets.forEach(target => {
      if (target.dataset.rendered) return;
      const host = target.closest('[data-qr-text]') || target;
      const text = host.dataset.qrText;
      if (!text) return;
      try {
        const small = target.classList.contains('qr-small');
        target.innerHTML = '';
        new QRCode(target, { text, width: small ? 116 : 240, height: small ? 116 : 240, colorDark: '#073b45', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.H });
        target.dataset.rendered = '1';
        $('[data-qr-loading]', host)?.remove();
      } catch (error) {
        target.innerHTML = `<p class="qr-error">Erro ao gerar QR: ${escapeHtml(error?.message || 'desconhecido')}</p>`;
      }
    });
  };
  renderQrs();

  $$('[data-download-qr]').forEach(button => button.addEventListener('click', () => {
    const card = button.closest('.panel-card,.tag-card') || document;
    const stage = $('[data-qr-target]', card);
    const canvas = stage?.querySelector('canvas');
    const img = stage?.querySelector('img');
    const href = canvas?.toDataURL('image/png') || img?.src;
    if (!href) return alert('Aguarde o QR ser gerado.');
    const a = document.createElement('a'); a.href = href; a.download = `QR-PetPass-${safeFilename(button.dataset.downloadQr || 'pet')}.png`; document.body.appendChild(a); a.click(); a.remove();
  }));

  $$('[data-copy-text]').forEach(button => button.addEventListener('click', async () => {
    const text = button.dataset.copyText || '';
    try { await navigator.clipboard.writeText(text); button.textContent = '✓ Copiado'; setTimeout(() => button.textContent = 'Copiar link', 1800); }
    catch { window.prompt('Copie este link:', text); }
  }));

  $$('[data-program-nfc]').forEach(button => button.addEventListener('click', async () => {
    const nfcUrl = button.dataset.programNfc;
    if (!nfcUrl) return;
    if ('NDEFReader' in window && window.isSecureContext) {
      try {
        const writer = new NDEFReader();
        await writer.write({ records: [{ recordType: 'url', data: nfcUrl }] });
        alert('NFC gravado. Faça uma leitura de teste antes de bloquear a tag.');
        return;
      } catch (_) { /* fallback below */ }
    }
    try { await navigator.clipboard.writeText(nfcUrl); alert('A URL NFC foi copiada. Grave-a como URL/URI no aplicativo NFC Tools: ' + nfcUrl); }
    catch { window.prompt('Copie esta URL e grave-a no NFC Tools:', nfcUrl); }
  }));

  const activationForm = $('[data-activation-form]');
  $('[data-activation-pet]')?.addEventListener('change', e => {
    if (activationForm) activationForm.action = `${window.PETPASS.baseUrl}/owner/pets/${encodeURIComponent(e.target.value)}/claim-tag`;
  });

  $$('[data-bot]').forEach(widget => {
    const panel = $('.bot-panel', widget), messages = $('[data-bot-messages]', widget), form = $('[data-bot-form]', widget);
    if (!panel || !messages || !form) return;
    $$('[data-bot-toggle]', widget).forEach(b => b.addEventListener('click', () => panel.hidden = !panel.hidden));
    form.addEventListener('submit', async e => {
      e.preventDefault(); const input = form.question, question = input.value.trim(); if (!question) return;
      const q = document.createElement('div'); q.className = 'bot-message bot-question'; q.textContent = question; messages.appendChild(q); input.value = '';
      const loading = document.createElement('div'); loading.className = 'bot-message bot-answer'; loading.textContent = 'Pensando com carinho…'; messages.appendChild(loading); messages.scrollTop = messages.scrollHeight;
      try {
        const response = await fetch(`${window.PETPASS.baseUrl}/api/bot`, { method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-Token': window.PETPASS.csrf }, body: JSON.stringify({ question }) });
        const data = await response.json(); loading.textContent = data.answer || 'Não encontrei uma resposta.';
      } catch (_) { loading.textContent = 'Não consegui responder agora. Tente novamente.'; }
      messages.scrollTop = messages.scrollHeight;
    });
  });

  const mapNode = $('#petpass-map');
  const startMap = (attempt = 0) => {
    if (!mapNode) return;
    if (!window.L) {
      if (attempt < 30) return setTimeout(() => startMap(attempt + 1), 150);
      mapNode.innerHTML = '<div class="map-error">O mapa não carregou. Confirme se a pasta assets/vendor/leaflet foi enviada.</div>';
      return;
    }
    let places = [];
    try { places = JSON.parse(mapNode.dataset.places || mapNode.dataset.partners || '[]'); }
    catch (_) { mapNode.innerHTML = '<div class="map-error">Os dados do mapa são inválidos.</div>'; return; }

    const map = L.map(mapNode, { scrollWheelZoom: true }).setView([-27.05, -48.61], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution:'© OpenStreetMap' }).addTo(map);
    const bounds = [];
    places.forEach(p => {
      const lat = Number(p.latitude), lng = Number(p.longitude); if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
      bounds.push([lat,lng]);
      const label = p.subtype || typeLabel(p.partner_type);
      const nav = p.navigation_url ? `<br><a href="${escapeAttr(p.navigation_url)}" target="_blank" rel="noopener">Como chegar</a>` : '';
      L.marker([lat,lng]).addTo(map).bindPopup(`<b>${escapeHtml(p.name)}</b><br>${escapeHtml(label)}<br>${escapeHtml(p.city || '')}${p.emergency_24h ? '<br><strong>Emergência 24h</strong>' : ''}${nav}`);
    });
    if (bounds.length > 1) map.fitBounds(bounds, { padding: [25,25], maxZoom: 13 });
    else if (bounds.length === 1) map.setView(bounds[0], 14);

    $('[data-near-me]')?.addEventListener('click', () => {
      if (!navigator.geolocation) return alert('Seu navegador não disponibiliza localização.');
      navigator.geolocation.getCurrentPosition(pos => {
        const here = [pos.coords.latitude, pos.coords.longitude];
        map.setView(here, 13); L.circleMarker(here, { radius: 9 }).addTo(map).bindPopup('Você está aqui').openPopup();
        sortByDistance(pos.coords.latitude, pos.coords.longitude);
      }, () => alert('Não foi possível acessar sua localização. Verifique a permissão do navegador.'), { enableHighAccuracy: true, timeout: 12000 });
    });
  };
  startMap();

  function sortByDistance(lat, lng) {
    const list = $('[data-distance-list]'); if (!list) return;
    const items = $$('[data-distance-item]', list).map(item => {
      const itemLat = Number(item.dataset.lat), itemLng = Number(item.dataset.lng);
      const distance = Number.isFinite(itemLat) && Number.isFinite(itemLng) ? haversine(lat,lng,itemLat,itemLng) : Infinity;
      const label = $('[data-distance-label]', item); if (label && Number.isFinite(distance)) label.textContent = `Aproximadamente ${distance < 1 ? Math.round(distance*1000)+' m' : distance.toFixed(1).replace('.',',')+' km'} de você`;
      return { item, distance };
    }).sort((a,b) => a.distance - b.distance);
    items.forEach(({item}) => list.appendChild(item));
  }

  $$('form[enctype="multipart/form-data"]').forEach(form => form.addEventListener('submit', () => {
    const submit = form.querySelector('button[type="submit"],button:not([type])');
    if (submit) { submit.disabled = true; submit.dataset.originalText = submit.textContent; submit.textContent = 'Enviando…'; }
  }));

  function haversine(lat1,lon1,lat2,lon2){ const R=6371,toRad=x=>x*Math.PI/180; const dLat=toRad(lat2-lat1),dLon=toRad(lon2-lon1); const a=Math.sin(dLat/2)**2+Math.cos(toRad(lat1))*Math.cos(toRad(lat2))*Math.sin(dLon/2)**2; return 2*R*Math.asin(Math.sqrt(a)); }
  function typeLabel(type){ return ({VETERINARY:'Veterinária',PETSHOP:'Pet shop',HOTEL:'Hotel pet friendly',RESTAURANT:'Restaurante pet friendly'})[type] || type || 'Local pet'; }
  function normalize(value){ return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim(); }
  function safeFilename(value){ return String(value).normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-zA-Z0-9_-]+/g,'-'); }
  function escapeHtml(value){ const div=document.createElement('div'); div.textContent=value??''; return div.innerHTML; }
  function escapeAttr(value){ return String(value??'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
})();

(() => {
  'use strict';
  const dialog = document.querySelector('[data-qr-scanner]');
  if (!dialog) return;
  const video = dialog.querySelector('[data-qr-scanner-video]');
  const status = dialog.querySelector('[data-qr-scanner-status]');
  let stream = null;
  let scanning = false;
  const stop = () => { scanning = false; if (stream) stream.getTracks().forEach(track => track.stop()); stream = null; video.srcObject = null; };
  const navigate = value => {
    const raw = String(value || '').trim();
    if (/^https?:\/\//i.test(raw)) { const url = new URL(raw, window.location.href); if (url.origin === window.location.origin) window.location.assign(url.href); else status.textContent = 'El QR no pertenece a esta aplicación.'; return; }
    const code = raw.toUpperCase().replace(/^SC-?/, '');
    if (!/^\d{4,}$/.test(code)) { status.textContent = 'Código no válido.'; return; }
    const url = new URL(window.location.href); url.search = ''; url.searchParams.set('modulo', 'control-escaneres'); url.searchParams.set('seccion', 'catalogo'); url.searchParams.set('q', `SC-${code}`); window.location.assign(url.href);
  };
  const detect = async () => {
    if (!scanning || !('BarcodeDetector' in window)) return;
    try { const codes = await new BarcodeDetector({ formats: ['qr_code'] }).detect(video); if (codes[0]?.rawValue) { stop(); navigate(codes[0].rawValue); return; } } catch (_) { status.textContent = 'No fue posible leer la imagen. Puedes escribir el código.'; }
    if (scanning) requestAnimationFrame(detect);
  };
  const open = async () => {
    dialog.showModal(); status.textContent = 'Solicitando permiso de cámara…';
    if (!navigator.mediaDevices?.getUserMedia || !('BarcodeDetector' in window)) { status.textContent = 'Este navegador no permite lectura QR directa. Usa el campo manual.'; return; }
    try { stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false }); video.srcObject = stream; await video.play(); scanning = true; status.textContent = 'Apunta la cámara al código QR.'; detect(); } catch (_) { stop(); status.textContent = 'Permiso denegado o cámara no disponible. Usa el campo manual.'; }
  };
  document.querySelector('[data-qr-scanner-open]')?.addEventListener('click', open);
  dialog.querySelector('[data-qr-scanner-close]')?.addEventListener('click', () => { stop(); dialog.close(); });
  dialog.addEventListener('cancel', stop); window.addEventListener('pagehide', stop);
  dialog.querySelector('[data-qr-manual-form]')?.addEventListener('submit', event => { event.preventDefault(); stop(); navigate(new FormData(event.currentTarget).get('code')); });
})();

(() => {
  'use strict';
  document.querySelectorAll('[data-signature-canvas]').forEach(canvas => {
    const input = canvas.parentElement.querySelector('[data-signature-input]');
    const clear = canvas.parentElement.querySelector('[data-signature-clear]');
    const context = canvas.getContext('2d'); let drawing = false; let signed = false;
    context.lineWidth = 2.5; context.lineCap = 'round'; context.strokeStyle = '#111827';
    const point = event => { const rect = canvas.getBoundingClientRect(); return { x: (event.clientX - rect.left) * canvas.width / rect.width, y: (event.clientY - rect.top) * canvas.height / rect.height }; };
    canvas.addEventListener('pointerdown', event => { drawing = true; signed = true; canvas.setPointerCapture(event.pointerId); const p = point(event); context.beginPath(); context.moveTo(p.x, p.y); });
    canvas.addEventListener('pointermove', event => { if (!drawing) return; const p = point(event); context.lineTo(p.x, p.y); context.stroke(); });
    const finish = () => { if (!drawing) return; drawing = false; input.value = signed ? canvas.toDataURL('image/png') : ''; };
    canvas.addEventListener('pointerup', finish); canvas.addEventListener('pointercancel', finish);
    clear.addEventListener('click', () => { context.clearRect(0, 0, canvas.width, canvas.height); input.value = ''; signed = false; });
  });
  document.querySelectorAll('[data-vo-operation-form]').forEach(form => form.addEventListener('submit', event => { const signatures=[...form.querySelectorAll('[data-signature-input]')]; let error=form.querySelector('[data-signature-error]'); if(signatures.some(input => !input.value)){event.preventDefault();if(!error){error=document.createElement('p');error.dataset.signatureError='';error.className='vo-alert vo-alert--error';error.setAttribute('role','alert');form.querySelector('[data-signature-group]')?.append(error);}error.textContent='Captura ambas firmas antes de continuar.';form.querySelector('[data-signature-canvas]')?.focus();}else if(error){error.remove();} }));
})();

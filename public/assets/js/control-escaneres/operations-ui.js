document.addEventListener('DOMContentLoaded', () => {
  'use strict';
  document.querySelectorAll('[data-vo-operation-form]').forEach(form => form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"],button:not([type])');
    if (!button || button.disabled) return;
    button.disabled = true; button.setAttribute('aria-busy', 'true'); button.dataset.originalLabel = button.textContent;
    button.textContent = button.dataset.loadingLabel || 'Registrando…';
  }));

  const resizeImage = file => new Promise(resolve => {
    if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size < 900000) return resolve(file);
    const image = new Image(); const url = URL.createObjectURL(file);
    image.onload = () => {
      const ratio = Math.min(1, 1600 / Math.max(image.width, image.height));
      const canvas = document.createElement('canvas'); canvas.width = Math.round(image.width * ratio); canvas.height = Math.round(image.height * ratio);
      canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height); URL.revokeObjectURL(url);
      canvas.toBlob(blob => resolve(blob && blob.size < file.size ? new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {type: 'image/jpeg', lastModified: Date.now()}) : file), 'image/jpeg', .82);
    };
    image.onerror = () => { URL.revokeObjectURL(url); resolve(file); }; image.src = url;
  });

  document.querySelectorAll('input[type="file"][accept*="image/"]').forEach(input => {
    const preview = document.createElement('div'); preview.className = 'ce-photo-preview'; preview.setAttribute('aria-live', 'polite'); input.insertAdjacentElement('afterend', preview);
    let selected = [];
    const sync = () => {
      const transfer = new DataTransfer(); selected.forEach(file => transfer.items.add(file)); input.files = transfer.files; preview.replaceChildren();
      selected.forEach((file, index) => {
        const item = document.createElement('div'); item.className = 'ce-photo-preview__item';
        const image = document.createElement('img'); image.alt = `Vista previa ${index + 1}`; const url = URL.createObjectURL(file); image.src = url; image.onload = () => URL.revokeObjectURL(url);
        const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = '×'; remove.setAttribute('aria-label', `Eliminar ${file.name}`); remove.addEventListener('click', () => { selected.splice(index, 1); sync(); });
        item.append(image, remove); preview.append(item);
      });
    };
    input.addEventListener('change', async () => { selected = await Promise.all([...input.files].map(resizeImage)); sync(); });
  });

  const error = document.querySelector('[role="alert"], [aria-invalid="true"]');
  if (error instanceof HTMLElement) { error.tabIndex = -1; error.focus({preventScroll: false}); }
});

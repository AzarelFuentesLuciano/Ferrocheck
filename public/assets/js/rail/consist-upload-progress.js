document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-consist-upload-form]');
    if (!form) {
        return;
    }

    const cards = Array.from(form.querySelectorAll('[data-consist-file]'));
    const stateLabels = {
        pending: 'Pendiente',
        selected: 'Archivo seleccionado',
        received: 'Archivo recibido',
        validating: 'Validando archivo',
        reading: 'Leyendo hoja',
        analyzing: 'Analizando información',
        valid: 'Validado',
        error: 'Error'
    };
    const stateMessages = {
        pending: 'Pendiente de validación.',
        selected: 'Archivo seleccionado.',
        received: 'Archivo recibido.',
        validating: 'Validando extensión, tamaño y contenido...',
        reading: 'Detectando hoja y encabezados...',
        analyzing: 'Analizando VIN, filas vacías y duplicados...',
        valid: 'Archivo validado correctamente.'
    };

    const update = (card, state, value, message = '') => {
        const bar = card.querySelector('[role="progressbar"]');
        const fill = card.querySelector('[data-progress-fill]');
        const percent = card.querySelector('[data-progress-percent]');
        const stateLabel = card.querySelector('[data-progress-state]');
        const status = card.querySelector('[data-progress-message]');
        card.dataset.state = state;
        bar?.setAttribute('aria-valuenow', String(value));
        if (fill) fill.style.width = `${value}%`;
        if (percent) percent.textContent = `${value} %`;
        if (stateLabel) stateLabel.textContent = stateLabels[state] || state;
        if (status) status.textContent = message || stateMessages[state] || '';
    };

    cards.forEach((card) => {
        const input = card.querySelector('input[type="file"]');
        input?.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file) {
                update(card, 'pending', 0);
                return;
            }
            const extension = file.name.split('.').pop()?.toLowerCase() || '';
            if (!['xlsx', 'xls', 'csv'].includes(extension)) {
                update(card, 'error', 0, 'La extensión seleccionada no está permitida.');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                update(card, 'error', 0, 'El archivo supera el máximo de 10 MB.');
                return;
            }
            update(card, 'selected', 10);
        });
    });

    form.addEventListener('submit', () => {
        cards.forEach((card) => {
            if (card.dataset.state === 'selected') update(card, 'received', 20);
        });
        [
            [120, 'validating', 40],
            [360, 'reading', 60],
            [700, 'analyzing', 80]
        ].forEach(([delay, state, value]) => {
            window.setTimeout(() => cards.forEach((card) => {
                if (card.dataset.state !== 'error') update(card, state, value);
            }), delay);
        });
    });
});

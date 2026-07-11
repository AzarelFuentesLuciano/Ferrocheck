document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('fileInput');
    const dropzone = document.getElementById('dropzone');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileType = document.getElementById('fileType');
    const importBtn = document.getElementById('importBtn');
    const progressFill = document.getElementById('progressFill');
    const progressPercent = document.getElementById('progressPercent');
    const statusMessage = document.getElementById('statusMessage');

    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const resetProgress = () => {
        progressFill.style.width = '0%';
        progressPercent.textContent = '0%';
        statusMessage.textContent = 'Listo para importar';
        statusMessage.style.color = 'var(--success)';
    };

    const updateFileDetails = (file) => {
        if (!file) {
            fileInfo.hidden = true;
            importBtn.disabled = true;
            resetProgress();
            return;
        }

        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileType.textContent = file.type || 'Tipo no disponible';
        fileInfo.hidden = false;
        importBtn.disabled = false;
        resetProgress();
    };

    fileInput.addEventListener('change', (event) => {
        const [file] = event.target.files;
        updateFileDetails(file);
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('drag-over');
        });
    });

    ['dragleave', 'dragend', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, () => {
            dropzone.classList.remove('drag-over');
        });
    });

    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        const [file] = event.dataTransfer.files;
        if (file) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;
            updateFileDetails(file);
        }
    });

    importBtn.addEventListener('click', () => {
        if (!fileInput.files.length) {
            return;
        }

        let progress = 0;
        statusMessage.textContent = 'Procesando archivo...';
        statusMessage.style.color = '#2563eb';

        const interval = window.setInterval(() => {
            progress += 10;
            progressFill.style.width = `${progress}%`;
            progressPercent.textContent = `${progress}%`;

            if (progress >= 100) {
                window.clearInterval(interval);
                statusMessage.textContent = 'Listo para importar';
                statusMessage.style.color = 'var(--success)';
            }
        }, 140);
    });
});
